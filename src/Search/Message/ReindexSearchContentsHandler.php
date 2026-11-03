<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Message;

use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchIndexer;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;

/**
 * Reindexes contents by id; the ones deleted since are removed from the index.
 */
final readonly class ReindexSearchContentsHandler
{
    public function __construct(
        private SearchResourceRegistry $resources,
        private SearchIndexer $indexer,
    ) {
    }

    public function __invoke(ReindexSearchContents $message): void
    {
        $resource = $this->resources->get($message->type);

        if (!$resource instanceof SearchResource) {
            return;
        }

        $missing = array_fill_keys($message->ids, true);

        foreach ($this->indexer->find($resource, $message->ids) as $entity) {
            unset($missing[(int) SearchDocumentFactory::id($entity)]);
            $this->indexer->index($entity);
        }

        foreach (array_keys($missing) as $id) {
            $this->indexer->remove($message->type, $id);
        }
    }
}
