<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Api;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\Pagination\TraversablePaginator;
use ApiPlatform\State\ProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchHit;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Gingerminds\CoreBundle\ApiPlatform\Filter\ComputedFilterStore;
use Gingerminds\CoreBundle\Repository\ListQuery;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The search endpoints (SearchItem), read from the configured engine. `/search-results` adds the
 * content of each hit (one query per type of the page, normalized with its `group`) and the facets.
 *
 * @implements ProviderInterface<SearchItem>
 */
final readonly class SearchProvider implements ProviderInterface
{
    private const int MAX_ITEMS_PER_PAGE = 100;

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
        private RequestStack $requestStack,
        private EntityManagerInterface $entityManager,
        private \Closure $normalizer,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): TraversablePaginator
    {
        $request = $context['request'] ?? $this->requestStack->getCurrentRequest();
        $query = $this->query($request instanceof Request ? ListQuery::fromRequest($request) : new ListQuery());
        $result = $this->engine->search($query);
        $items = array_map($this->item(...), $result->hits);

        if (true === ($operation->getExtraProperties()[SearchItem::WITH_RESOURCES] ?? false)) {
            $this->attachResources($items, ($request instanceof Request ? $request->getRequestFormat() : null) ?? 'jsonld');
            $this->filterStore->set(SearchItem::class, $this->filters->compute($query, $result, $this->languageContext->current()?->getIso()));
        }

        return new TraversablePaginator(new \ArrayIterator($items), $query->page, $query->itemsPerPage, $result->total);
    }

    private function item(SearchHit $hit): SearchItem
    {
        return new SearchItem($hit->recordId, $hit->type, $hit->title, $hit->url, $hit->excerpt, $hit->publishedAt);
    }

    private function query(ListQuery $list): SearchQuery
    {
        $text = $list->getFilter(ListQuery::SEARCH_FILTER);
        [$types, $categories] = $this->typeFilter($list->getFilter('type'));

        return new SearchQuery(
            $this->siteContext->id(),
            $this->languageContext->ids(),
            \is_string($text) ? $text : null,
            $types,
            $categories,
            $list->page,
            min(self::MAX_ITEMS_PER_PAGE, $list->itemsPerPage ?? 10),
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

        foreach (\is_array($value) ? $value : [$value] as $item) {
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
     * @param list<SearchItem> $items
     */
    private function attachResources(array $items, string $format): void
    {
        $normalizer = ($this->normalizer)();
        $ids = [];

        foreach ($items as $item) {
            $ids[$item->type][] = $item->id;
        }

        foreach ($ids as $type => $typeIds) {
            $resource = $this->resources->get($type);

            if (null === $resource?->group) {
                continue;
            }

            $entities = $this->entities($resource, $typeIds);

            foreach ($items as $item) {
                if ($item->type === $type && isset($entities[$item->id])) {
                    $item->resource = $this->normalize($normalizer, $entities[$item->id], $format, $resource->group);
                }
            }
        }
    }

    /**
     * The contents of a type, in one query.
     *
     * @param list<int> $ids
     *
     * @return array<int, object> by id
     */
    private function entities(SearchResource $resource, array $ids): array
    {
        $entities = [];

        foreach ($this->entityManager->getRepository($resource->entity)->findBy(['id' => $ids]) as $entity) {
            $id = SearchDocumentFactory::id($entity);

            if (null !== $id) {
                $entities[$id] = $entity;
            }
        }

        return $entities;
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
