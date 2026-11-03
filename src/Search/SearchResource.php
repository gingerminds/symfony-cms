<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A type of the search index: an entity and its #[SearchIndex].
 */
final readonly class SearchResource
{
    /**
     * @param class-string<SearchIndexableInterface> $entity
     */
    public function __construct(
        public string $type,
        public string $entity,
        public ?string $label = null,
        public string $translationDomain = 'messages',
        public ?string $category = null,
        public ?string $group = null,
    ) {
    }

    /**
     * @param class-string<SearchIndexableInterface> $entity
     */
    public static function fromAttribute(string $entity, SearchIndex $index): self
    {
        return new self($index->type, $entity, $index->label, $index->translationDomain, $index->category, $index->group);
    }
}
