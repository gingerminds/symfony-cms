<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * An entity of the public search index (SearchIndexableInterface), under `type` (`/api/search-*`
 * hits and facet). Read on the class or its parents, the closest declaration winning: an entity
 * overriding a bundle one keeps it.
 *
 * - `label` / `translationDomain`: the label of the type facet (null: the type);
 * - `category`: the association to the categories (category facets, reindex of a hidden category);
 * - `group`: the serialization group of the content in `/api/search-results` (null: no content).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class SearchIndex
{
    public function __construct(
        public string $type,
        public ?string $label = null,
        public string $translationDomain = 'messages',
        public ?string $category = null,
        public ?string $group = null,
    ) {
    }
}
