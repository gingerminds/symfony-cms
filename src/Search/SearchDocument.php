<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content in one language, as given to the search engine.
 */
final readonly class SearchDocument
{
    /**
     * @param array<string, string> $fields      searchable text by field (plain text)
     * @param array<string, int>    $weights     weight by field, for the engines supporting it
     * @param list<int>             $categoryIds
     */
    public function __construct(
        public string $type,
        public int $recordId,
        public ?int $siteId,
        public int $languageId,
        public string $title,
        public string $url,
        public ?string $excerpt,
        public array $fields,
        public array $weights = [],
        public ?\DateTimeImmutable $publishedAt = null,
        public array $categoryIds = [],
    ) {
    }

    /**
     * The searchable text of every field.
     */
    public function text(): string
    {
        return implode(' ', array_filter($this->fields, static fn (string $text): bool => '' !== $text));
    }
}
