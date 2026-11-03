<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * The types of the search index: the Doctrine entities implementing SearchIndexableInterface with
 * a #[SearchIndex], by type (cached in `cache.system`, cleared by `cache:clear`).
 */
class SearchResourceRegistry
{
    private const string CACHE_KEY = 'gingerminds_cms.search.resources';

    /** @var array<string, SearchResource>|null */
    private ?array $resources = null;

    /** @var array<class-string, SearchIndex|null> */
    private array $attributes = [];

    public function __construct(
        private readonly ManagerRegistry $doctrine,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * @return array<string, SearchResource>
     */
    public function all(): array
    {
        return $this->resources ??= array_map(
            static fn (array $resource): SearchResource => SearchResource::fromAttribute($resource['entity'], new SearchIndex(...$resource['index'])),
            $this->cache->get(self::CACHE_KEY, $this->discover(...)),
        );
    }

    public function get(string $type): ?SearchResource
    {
        return $this->all()[$type] ?? null;
    }

    /**
     * The type of an entity (its #[SearchIndex], parents included), null when not indexed.
     */
    public function forEntity(object $entity): ?SearchResource
    {
        if (!$entity instanceof SearchIndexableInterface) {
            return null;
        }

        $index = $this->attribute($entity::class);

        return $index instanceof SearchIndex ? $this->get($index->type) : null;
    }

    /**
     * @return array<string, array{entity: class-string<SearchIndexableInterface>, index: array<string, mixed>}>
     */
    private function discover(): array
    {
        $resources = [];

        foreach ($this->doctrine->getManagers() as $manager) {
            foreach ($manager->getMetadataFactory()->getAllMetadata() as $metadata) {
                $class = $metadata->getName();
                $index = is_a($class, SearchIndexableInterface::class, true) && !new \ReflectionClass($class)->isAbstract() ? $this->attribute($class) : null;

                if ($index instanceof SearchIndex) {
                    $resources[$index->type] = ['entity' => $class, 'index' => get_object_vars($index)];
                }
            }
        }

        return $resources;
    }

    /**
     * @param class-string $class
     */
    private function attribute(string $class): ?SearchIndex
    {
        if (\array_key_exists($class, $this->attributes)) {
            return $this->attributes[$class];
        }

        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            foreach ($reflection->getAttributes(SearchIndex::class) as $attribute) {
                return $this->attributes[$class] = $attribute->newInstance();
            }
        }

        return $this->attributes[$class] = null;
    }
}
