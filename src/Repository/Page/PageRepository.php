<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Repository\Page;

use Doctrine\ORM\AbstractQuery;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CoreBundle\Model\FilterableInterface;
use Gingerminds\CoreBundle\Repository\AbstractFacetRepository;

/**
 * @extends AbstractFacetRepository<PageInterface&FilterableInterface>
 */
class PageRepository extends AbstractFacetRepository
{
    /**
     * @param class-string<PageInterface&FilterableInterface> $entityClass
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

    /**
     * The published page of a URL path (`page_urls`), tried in each language, best first.
     *
     * @param list<int> $languageIds
     *
     * @return (PageInterface&FilterableInterface)|null
     */
    public function findPublishedByPath(string $path, int $siteId, array $languageIds): ?PageInterface
    {
        $path = implode('/', array_filter(explode('/', $path), static fn (string $segment): bool => '' !== $segment));

        foreach ([] !== $languageIds ? $languageIds : [null] as $languageId) {
            $qb = $this->getEntityManager()->createQueryBuilder()
                ->select('IDENTITY(u.page)')
                ->from(PageUrl::class, 'u')
                ->join('u.page', 'p')
                ->andWhere('u.site = :site')
                ->andWhere('u.path = :path')
                ->andWhere('p.status = :published')
                ->setParameter('site', $siteId)
                ->setParameter('path', $path)
                ->setParameter('published', PublicationStatus::PUBLISHED)
                ->setMaxResults(1);

            if (null !== $languageId) {
                $qb->andWhere('u.language = :language')->setParameter('language', $languageId);
            }

            $pageId = $qb->getQuery()->getOneOrNullResult(AbstractQuery::HYDRATE_SINGLE_SCALAR);

            if (null !== $pageId) {
                return $this->findOneForRead((string) $pageId);
            }
        }

        return null;
    }

    /**
     * The pages of these categories.
     *
     * @param list<int> $categoryIds
     *
     * @return list<PageInterface&FilterableInterface>
     */
    public function findInCategories(array $categoryIds): array
    {
        if ([] === $categoryIds) {
            return [];
        }

        /** @var list<PageInterface&FilterableInterface> */
        return $this->createQueryBuilder('p')
            ->andWhere('p.category IN (:categories)')
            ->setParameter('categories', $categoryIds)
            ->getQuery()
            ->getResult();
    }
}
