<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A page of hits, their total and the facets: hits by type and by (type, category), counted
 * against the text and the site, without the type filter.
 */
final readonly class SearchResult
{
    /**
     * @param list<SearchHit>                $hits
     * @param array<string, int>             $typeCounts     type => hits
     * @param array<string, array<int, int>> $categoryCounts type => (category id => hits)
     */
    public function __construct(
        public array $hits,
        public int $total,
        public array $typeCounts = [],
        public array $categoryCounts = [],
    ) {
    }
}
