<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * A content of the search index in one language: the hit (title, URL, excerpt) and the searched
 * texts besides the title (HTML allowed: indexed as plain text).
 */
final readonly class SearchEntry
{
    /**
     * @param list<?string> $texts
     */
    public function __construct(
        public LanguageInterface $language,
        public string $title,
        public string $url,
        public ?string $excerpt = null,
        public array $texts = [],
    ) {
    }
}
