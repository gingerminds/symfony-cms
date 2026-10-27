<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Message;

/**
 * A category (and its subcategories) hidden from search or shown again: its contents are
 * reindexed. Handled synchronously unless the project routes it to an async transport.
 */
final readonly class ReindexSearchCategory
{
    /**
     * @param class-string $categoryClass
     */
    public function __construct(
        public string $categoryClass,
        public int $categoryId,
    ) {
    }
}
