<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A searchable content type (`gingerminds_cms.search.resources.<type>`).
 */
final readonly class SearchResource
{
    /**
     * @param class-string<PubliclySearchableInterface> $entity
     * @param array<string, int>                        $fields property path => weight (> 0)
     */
    public function __construct(
        public string $type,
        public string $entity,
        public array $fields,
        public ?string $label = null,
        public string $translationDomain = 'GingermindsCms',
        public ?string $category = null,
        public ?string $group = null,
    ) {
    }
}
