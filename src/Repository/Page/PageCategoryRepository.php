<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Page;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
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
     * The children of a deleted category move to the root (as `parent_id` ON DELETE SET NULL),
     * whatever the database.
     */
    protected function beforeRemove(object $entity): void
    {
        if ($entity instanceof PageCategoryInterface) {
            foreach ($entity->getChildren() as $child) {
                $child->setParent(null);
            }
        }
    }
}
