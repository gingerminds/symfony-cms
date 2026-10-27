<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * The searchable content types, in configuration order.
 */
class SearchResourceRegistry
{
    /** @var array<string, SearchResource> */
    private array $resources = [];

    /**
     * @param array<string, array{entity: class-string<PubliclySearchableInterface>, label: ?string, translation_domain: string, category: ?string, group: ?string, fields: array<string, array{weight: int}>}> $resources
     */
    public function __construct(array $resources)
    {
        foreach ($resources as $type => $resource) {
            $fields = array_filter(array_map(static fn (array $field): int => $field['weight'], $resource['fields']), static fn (int $weight): bool => $weight > 0);

            $this->resources[$type] = new SearchResource(
                $type,
                $resource['entity'],
                $fields,
                $resource['label'],
                $resource['translation_domain'],
                $resource['category'],
                $resource['group'],
            );
        }
    }

    /**
     * @return array<string, SearchResource>
     */
    public function all(): array
    {
        return $this->resources;
    }

    public function get(string $type): ?SearchResource
    {
        return $this->resources[$type] ?? null;
    }

    /**
     * The type of an entity (its class or a parent one), null when not searchable.
     */
    public function forEntity(object $entity): ?SearchResource
    {
        foreach ($this->resources as $resource) {
            if ($entity instanceof $resource->entity) {
                return $resource;
            }
        }

        return null;
    }
}
