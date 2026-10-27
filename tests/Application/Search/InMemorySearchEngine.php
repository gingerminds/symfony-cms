<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Search;

use Gingerminds\CmsBundle\Search\SearchDocument;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchHit;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResult;

/**
 * A project search engine (`test_memory_search` environment): the documents in memory, a search on
 * their text in the first language only.
 */
final class InMemorySearchEngine implements SearchEngineInterface
{
    /** @var array<string, list<SearchDocument>> "type:id" => documents */
    public array $documents = [];

    public function index(string $type, int $recordId, array $documents): void
    {
        $this->documents[$type . ':' . $recordId] = $documents;
    }

    public function remove(string $type, int $recordId): void
    {
        unset($this->documents[$type . ':' . $recordId]);
    }

    public function clear(?string $type = null): void
    {
        $this->documents = null === $type ? [] : array_filter($this->documents, static fn (string $key): bool => !str_starts_with($key, $type . ':'), \ARRAY_FILTER_USE_KEY);
    }

    public function search(SearchQuery $query): SearchResult
    {
        $hits = [];

        foreach ($this->documents as $documents) {
            foreach ($documents as $document) {
                if ($document->languageId === ($query->languageIds[0] ?? null) && (null === $query->text || str_contains(mb_strtolower($document->text()), mb_strtolower($query->text)))) {
                    $hits[] = new SearchHit($document->type, $document->recordId, $document->languageId, $document->title, $document->url, $document->excerpt, $document->publishedAt);
                }
            }
        }

        return new SearchResult(\array_slice($hits, $query->offset(), $query->itemsPerPage), \count($hits));
    }
}
