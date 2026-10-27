<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content found by the search engine.
 */
final readonly class SearchHit
{
    public function __construct(
        public string $type,
        public int $recordId,
        public int $languageId,
        public string $title,
        public string $url,
        public ?string $excerpt = null,
        public ?\DateTimeImmutable $publishedAt = null,
    ) {
    }
}
