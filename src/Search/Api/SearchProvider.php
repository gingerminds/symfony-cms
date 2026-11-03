<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\Pagination;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchHit;
use Gingerminds\CmsBundle\Search\SearchIndexer;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Gingerminds\CoreBundle\ApiPlatform\Filter\ComputedFilterStore;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The search endpoints (SearchItem), read from the configured engine, each hit checked against its
 * content (one query per type of the page). `/search-results` adds that content (normalized with
 * the `group` of its type) and the facets.
 *
 * @implements ProviderInterface<SearchItem>
 */
final readonly class SearchProvider implements ProviderInterface
{
    /**
     * @param \Closure(): NormalizerInterface $normalizer lazy: the serializer builds the API Platform providers
     */
    public function __construct(
        private SearchEngineInterface $engine,
        private SearchResourceRegistry $resources,
        private SearchFilterComputeService $filters,
        private ComputedFilterStore $filterStore,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
        private Pagination $pagination,
        private SearchIndexer $indexer,
        private \Closure $normalizer,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $request = $context['request'] ?? null;
        $query = $this->query($operation, $context);
        $result = $this->engine->search($query);
        $entities = $this->visibleEntities($result->hits);
        $items = [];

        foreach ($result->hits as $hit) {
            if (isset($entities[$hit->type][$hit->recordId])) {
                $items[] = $this->item($hit);
            }
        }

        if (true === ($operation->getExtraProperties()[SearchItem::WITH_RESOURCES] ?? false)) {
            $this->attachResources($items, $entities, ($request instanceof Request ? $request->getRequestFormat() : null) ?? 'jsonld');
            $this->filterStore->set(SearchItem::class, $this->filters->compute($query, $result, $this->languageContext->current()?->getIso()));
        }

        return new TraversablePaginator(new \ArrayIterator($items), $query->page, $query->itemsPerPage, $result->total);
    }

    private function item(SearchHit $hit): SearchItem
    {
        return new SearchItem($hit->recordId, $hit->type, $hit->title, $hit->url, $hit->excerpt, $hit->publishedAt);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function query(Operation $operation, array $context): SearchQuery
    {
        $parameters = $operation->getParameters();
        $text = $parameters?->get(SearchItem::SEARCH)?->getValue();
        [$types, $categories] = $this->typeFilter($parameters?->get(SearchItem::TYPE)?->getValue());

        return new SearchQuery(
            $this->siteContext->id(),
            $this->languageContext->ids(),
            \is_string($text) && '' !== $text ? $text : null,
            $types,
            $categories,
            $this->pagination->getPage($context),
            $this->pagination->getLimit($operation, $context),
        );
    }

    /**
     * `page` (a whole type) and `page:12` (a category of a type), known types only.
     *
     * @return array{list<string>, array<string, list<int>>}
     */
    private function typeFilter(mixed $value): array
    {
        $types = [];
        $categories = [];

        // A missing parameter (ParameterNotFound) is not scalar: skipped below.
        $values = \is_array($value) ? $value : [$value];

        foreach ($values as $item) {
            $parts = explode(':', \is_scalar($item) ? (string) $item : '', 2);
            $type = $parts[0];
            $categoryId = $parts[1] ?? null;

            if (!$this->resources->get($type) instanceof SearchResource) {
                continue;
            }

            if (null === $categoryId) {
                $types[] = $type;
            } elseif (ctype_digit($categoryId)) {
                $categories[$type][] = (int) $categoryId;
            }
        }

        return [array_values(array_unique($types)), $categories];
    }

    /**
     * The contents of the hits still visible (published, not excluded), by type and id, in one
     * query per type with its eager loads: a hit of an index out of sync (DQL write, async engine)
     * is not served.
     *
     * @param list<SearchHit> $hits
     *
     * @return array<string, array<int, object>>
     */
    private function visibleEntities(array $hits): array
    {
        $ids = [];
        $entities = [];

        foreach ($hits as $hit) {
            $ids[$hit->type][] = $hit->recordId;
        }

        foreach ($ids as $type => $typeIds) {
            $resource = $this->resources->get($type);

            if (!$resource instanceof SearchResource) {
                continue;
            }

            foreach ($this->indexer->find($resource, $typeIds) as $entity) {
                $id = SearchDocumentFactory::id($entity);

                if (null !== $id && SearchIndexer::isVisible($entity)) {
                    $entities[$type][$id] = $entity;
                }
            }
        }

        return $entities;
    }

    /**
     * @param list<SearchItem>                  $items
     * @param array<string, array<int, object>> $entities
     */
    private function attachResources(array $items, array $entities, string $format): void
    {
        $normalizer = ($this->normalizer)();

        foreach ($items as $item) {
            $group = $this->resources->get($item->type)?->group;

            if (null !== $group && isset($entities[$item->type][$item->id])) {
                $item->resource = $this->normalize($normalizer, $entities[$item->type][$item->id], $format, $group);
            }
        }
    }

    /**
     * A content as in its own list.
     *
     * @return array<string, mixed>|null
     */
    private function normalize(NormalizerInterface $normalizer, object $entity, string $format, string $group): ?array
    {
        $normalized = $normalizer->normalize($entity, $format, ['groups' => [$group]]);

        return \is_array($normalized) ? $normalized : null;
    }
}
