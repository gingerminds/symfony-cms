<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Page;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;
use Gingerminds\CoreBundle\Repository\TreeRepositoryInterface;
use Gingerminds\CoreBundle\Repository\TreeRepositoryTrait;

/**
 * @extends AbstractRepository<PageCategoryInterface>
 */
class PageCategoryRepository extends AbstractRepository implements TreeRepositoryInterface
{
    use TreeRepositoryTrait;

    /**
     * @param class-string<PageCategoryInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * The children of a deleted category move to the root and its pages lose it, in the unit of
     * work too (the ON DELETE SET NULL foreign keys only change the rows): PageUrlListener syncs
     * their URLs without the deleted category.
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
