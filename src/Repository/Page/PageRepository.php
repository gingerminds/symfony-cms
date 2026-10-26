<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Page;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CoreBundle\Repository\AbstractRepository;

/**
 * @extends AbstractRepository<PageInterface>
 */
class PageRepository extends AbstractRepository
{
    /**
     * @param class-string<PageInterface> $entityClass
     */
    public function __construct(ManagerRegistry $registry, string $entityClass)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * The pages of a category, `$except` excluded.
     */
    public function countInCategory(PageCategoryInterface $category, ?PageInterface $except = null): int
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.category = :category')
            ->setParameter('category', $category->getId());

        if (null !== $except?->getId()) {
            $qb->andWhere('p.id <> :page')->setParameter('page', $except->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}
