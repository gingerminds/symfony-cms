<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CoreBundle\Model\FilterableInterface;
use Gingerminds\CoreBundle\Repository\ListQuery;
use Gingerminds\CoreBundle\Service\AbstractFilterComputeService;

/**
 * The facets of the page collection API (Laravel PageFilterComputeService): publication dates,
 * categories with their page count against the other active filters. The status is always
 * `published` on the API: no status facet.
 *
 * @extends AbstractFilterComputeService<PageInterface&FilterableInterface>
 */
class PageFilterComputeService extends AbstractFilterComputeService
{
    /**
     * @param class-string<PageInterface&FilterableInterface> $entityClass
     */
    public function __construct(
        private readonly PageRepository $pages,
        private readonly string $entityClass,
    ) {
    }

    /**
     * @return array<string, array{type: string, options: mixed}>
     */
    public function compute(ListQuery $query): array
    {
        return [
            ...$this->computeDateFilters($query),
            'category' => ['type' => 'select-entity', 'options' => $this->categories($query)],
        ];
    }

    /**
     * @return list<array{value: int, label: string, total: int}>
     */
    public function categories(ListQuery $query): array
    {
        return $this->resolveCategoryOptions(
            PageCategoryInterface::class,
            $this->pages->getAssociationFacetCounts($query, 'category'),
            $this->normalizeIds($this->activeFilterValue($query, 'category')),
        );
    }

    protected function getEntityClass(): string
    {
        return $this->entityClass;
    }

    protected function dateFacetStats(ListQuery $query): array
    {
        return ['publishedAt' => $this->pages->getDateFacetStats($query, 'publishedAt')];
    }
}
