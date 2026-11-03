<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content in one language, as given to the search engine.
 */
final readonly class SearchDocument
{
    /**
     * @param string    $text        the searched text (plain text: the title and the entry texts)
     * @param list<int> $categoryIds
     */
    public function __construct(
        public string $type,
        public int $recordId,
        public ?int $siteId,
        public int $languageId,
        public string $title,
        public string $url,
        public ?string $excerpt,
        public string $text,
        public ?\DateTimeImmutable $publishedAt = null,
        public array $categoryIds = [],
    ) {
    }
}
