<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * A searchable content in one language: the result shown (title, URL, excerpt) and the object the
 * configured fields are read on (its translation; the content itself when null).
 */
final readonly class SearchEntry
{
    public function __construct(
        public LanguageInterface $language,
        public string $title,
        public string $url,
        public ?string $excerpt = null,
        public ?object $subject = null,
    ) {
    }
}
