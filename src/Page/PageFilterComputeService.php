<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use ApiPlatform\Metadata\Parameters;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CoreBundle\ApiPlatform\Filter\ComputedFilterStore;
use Gingerminds\CoreBundle\ApiPlatform\Filter\FilterParameters;

/**
 * The facets of the page collection API (Laravel PageFilterComputeService), against the other
 * valued list parameters: publication dates, categories with their page count. The status is
 * always `published` on the API: no status facet.
 *
 * @phpstan-import-type ComputedFilters from ComputedFilterStore
 */
class PageFilterComputeService
{
    public function __construct(
        private readonly PageRepository $pages,
        private readonly PageCategoryRepository $categories,
    ) {
    }

    /**
     * @return ComputedFilters
     */
    public function compute(Parameters $parameters): array
    {
        $filters = [];
        $dates = $this->pages->getDateFacetStats($parameters, 'publishedAt');

        if (null !== $dates['min']) {
            $filters['publishedAt'] = ['type' => 'date', 'options' => $dates];
        }

        $filters['category'] = ['type' => 'relation', 'options' => $this->categories($parameters)];

        return $filters;
    }

    /**
     * The categories with pages, and the selected ones.
     *
     * @return list<array{value: int, label: string, total: int}>
     */
    public function categories(Parameters $parameters): array
    {
        $counts = $this->pages->getAssociationFacetCounts($parameters, 'category');
        $value = FilterParameters::value($parameters, 'category');
        $selected = array_map(intval(...), array_filter(\is_array($value) ? $value : [$value], is_numeric(...)));
        $ids = array_values(array_unique([...array_map(intval(...), array_keys($counts)), ...$selected]));
        $options = [];

        // The list query: their translations fetch-joined (`#[EagerLoad]`), labelled without a
        // query per category.
        $found = [] === $ids ? [] : $this->categories->createListQueryBuilder()
            ->andWhere('o.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();

        foreach ($found as $category) {
            $id = (int) $category->getId();
            $total = $counts[$id] ?? 0;

            if ($category instanceof PageCategoryInterface && ($total > 0 || \in_array($id, $selected, true))) {
                $options[] = ['value' => $id, 'label' => (string) $category, 'total' => $total];
            }
        }

        return $options;
    }
}
