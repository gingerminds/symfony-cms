<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Page;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Symfony\Component\Form\FormInterface;

/**
 * @extends AbstractRepository<PageCategoryInterface>
 */
class PageCategoryRepository extends AbstractRepository
{
    /**
     * @param class-string<PageCategoryInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * Every category of the current site with its translations, by a single query, ordered by
     * position then code within each level.
     *
     * @return list<PageCategoryInterface>
     */
    public function findTree(): array
    {
        /** @var list<PageCategoryInterface> */
        return $this->createQueryBuilder('c')
            ->addSelect('t', 'l')
            ->leftJoin('c.translations', 't')
            ->leftJoin('t.language', 'l')
            ->orderBy('c.position')
            ->addOrderBy('c.code')
            ->getQuery()
            ->getResult();
    }

    /**
     * These categories and all their descendants.
     *
     * @param list<int> $ids
     *
     * @return list<int>
     */
    public function findIdsWithDescendants(array $ids): array
    {
        $children = [];

        foreach ($this->createQueryBuilder('c')->select('c.id', 'IDENTITY(c.parent) AS parent')->getQuery()->getArrayResult() as $row) {
            $children[(int) $row['parent']][] = (int) $row['id'];
        }

        $result = [];
        $pending = $ids;

        while ([] !== $pending) {
            $id = array_pop($pending);

            if (!isset($result[$id])) {
                $result[$id] = true;
                array_push($pending, ...($children[$id] ?? []));
            }
        }

        return array_keys($result);
    }

    /**
     * Applies the order of one level of the tree: ids of other levels are ignored.
     *
     * @param list<int> $ids
     */
    public function reorder(?int $parentId, array $ids): void
    {
        $siblings = [];

        foreach ($this->findBy(['parent' => $parentId]) as $category) {
            $siblings[(int) $category->getId()] = $category;
        }

        foreach (array_values($ids) as $position => $id) {
            if (isset($siblings[$id])) {
                $siblings[$id]->setPosition($position);
            }
        }

        $this->getEntityManager()->flush();
    }

    /**
     * A new category, or one moved to another parent, goes to the end of its level.
     */
    protected function beforeSave(object $entity, ?FormInterface $form): void
    {
        if (!$entity instanceof PageCategoryInterface) {
            return;
        }

        $original = $this->getEntityManager()->getUnitOfWork()->getOriginalEntityData($entity);

        if ([] !== $original && ($original['parent'] ?? null) === $entity->getParent()) {
            return;
        }

        $qb = $this->createQueryBuilder('c')->select('MAX(c.position)');
        $parent = $entity->getParent();

        if (!$parent instanceof PageCategoryInterface) {
            $qb->andWhere('c.parent IS NULL');
        } else {
            $qb->andWhere('c.parent = :parent')->setParameter('parent', $parent->getId());
        }

        if (null !== $entity->getId()) {
            $qb->andWhere('c.id <> :id')->setParameter('id', $entity->getId());
        }

        $max = $qb->getQuery()->getSingleScalarResult();
        $entity->setPosition(null === $max ? 0 : (int) $max + 1);
    }

    /**
     * The children of a deleted category move to the root and its pages lose it (as the ON DELETE
     * SET NULL foreign keys), whatever the database: their URLs are synced.
     */
    protected function beforeRemove(object $entity): void
    {
        if (!$entity instanceof PageCategoryInterface) {
            return;
        }

        foreach ($entity->getChildren() as $child) {
            $child->setParent(null);
        }

        $pages = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from(PageInterface::class, 'p')
            ->andWhere('p.category = :category')
            ->setParameter('category', $entity->getId())
            ->getQuery()
            ->getResult();

        foreach ($pages as $page) {
            if ($page instanceof PageInterface) {
                $page->setCategory(null);
            }
        }
    }
}
