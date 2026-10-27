<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A search engine (`gingerminds_cms.search.engine`: `doctrine`, or the id of a service of the
 * project: TNTSearch, Elasticsearch...). The bundle keeps it in sync (SearchIndexer) and reads it
 * for the search API.
 */
interface SearchEngineInterface
{
    /**
     * Replaces the documents of a content (every language: a missing language is removed).
     *
     * @param list<SearchDocument> $documents
     */
    public function index(string $type, int $recordId, array $documents): void;

    public function remove(string $type, int $recordId): void;

    /**
     * Removes every document of a type (every type when null), before a full reindex.
     */
    public function clear(?string $type = null): void;

    public function search(SearchQuery $query): SearchResult;
}
