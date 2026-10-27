<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A search: one version per content, in the first of `languageIds` it has (the current language,
 * then the fallback one with `translation_fallback`).
 */
final readonly class SearchQuery
{
    /**
     * @param list<int>                $languageIds by priority
     * @param list<string>             $types       whole types
     * @param array<string, list<int>> $categories  type => category ids (OR with the types)
     */
    public function __construct(
        public ?int $siteId,
        public array $languageIds,
        public ?string $text = null,
        public array $types = [],
        public array $categories = [],
        public int $page = 1,
        public int $itemsPerPage = 10,
    ) {
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->itemsPerPage;
    }

    /**
     * Whether a type filter (types or categories) applies.
     */
    public function filtersTypes(): bool
    {
        return [] !== $this->types || [] !== $this->categories;
    }
}
