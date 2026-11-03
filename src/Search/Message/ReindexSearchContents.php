<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Message;

/**
 * Contents of a type to reindex, sent by SearchIndexListener when a flush changes more of them
 * than `gingerminds_cms.search.message_threshold` (a category renamed: its pages URLs). Handled
 * synchronously unless the project routes it to an async transport.
 */
final readonly class ReindexSearchContents
{
    /**
     * @param list<int> $ids
     */
    public function __construct(
        public string $type,
        public array $ids,
    ) {
    }
}
