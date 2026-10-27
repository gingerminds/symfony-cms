<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The references of a content (file and media ids...) replaced by their entity: one query per
 * field type for the whole content (BlockReferenceResolverInterface), the repeater rows included.
 * `resolve()` serves the API (each entity normalized), `entities()` the previews. A missing entity
 * gives null (it is removed from a list); the stored content keeps its ids.
 */
class ContentReferenceResolver
{
    /**
     * @param ServiceProviderInterface<BlockReferenceResolverInterface> $resolvers field type => resolver
     */
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly ServiceProviderInterface $resolvers,
    ) {
    }

    /**
     * @param list<mixed>|null $content
     *
     * @return list<mixed>|null
     */
    public function resolve(?array $content): ?array
    {
        return null === $content ? null : $this->replace($content, true);
    }

    /**
     * @param list<mixed> $content
     *
     * @return list<mixed> the content, its references being entities
     */
    public function entities(array $content): array
    {
        return $this->replace($content, false);
    }

    /**
     * @param list<mixed> $content
     *
     * @return list<mixed>
     */
    private function replace(array $content, bool $normalize): array
    {
        $loaded = $this->load($content);

        return $this->walk($content, function (BlockField $field, mixed $value) use ($loaded, $normalize): mixed {
            $find = fn (mixed $id): mixed => $this->find($field, $id, $loaded, $normalize);

            if ($field->multiple) {
                return array_values(array_filter(array_map($find, \is_array($value) ? $value : []), static fn (mixed $entity): bool => null !== $entity));
            }

            return $find($value);
        });
    }

    /**
     * The entities of every reference of the content, one load per field type.
     *
     * @param list<mixed> $content
     *
     * @return array<string, array<int|string, object>> field type => entities by id
     */
    private function load(array $content): array
    {
        $ids = [];
        $this->walk($content, static function (BlockField $field, mixed $value) use (&$ids): mixed {
            $values = $field->multiple && \is_array($value) ? $value : [$value];
            $ids[$field->type] = [...$ids[$field->type] ?? [], ...array_filter($values, self::isId(...))];

            return $value;
        });

        $loaded = [];

        foreach ($ids as $type => $typeIds) {
            $loaded[$type] = [] === $typeIds ? [] : $this->resolvers->get($type)->load(array_values(array_unique($typeIds)));
        }

        return $loaded;
    }

    /**
     * The entity of an id (normalized for the API), null when missing.
     *
     * @param array<string, array<int|string, object>> $loaded
     */
    private function find(BlockField $field, mixed $id, array $loaded, bool $normalize): mixed
    {
        $entity = self::isId($id) ? $loaded[$field->type][(string) $id] ?? null : null;

        return null !== $entity && $normalize ? $this->resolvers->get($field->type)->normalize($entity) : $entity;
    }

    /**
     * @phpstan-assert-if-true int|non-empty-string $value
     */
    private static function isId(mixed $value): bool
    {
        return \is_int($value) || (\is_string($value) && '' !== $value);
    }

    /**
     * Applies $callback to the value of each reference field of the blocks (repeater rows included).
     *
     * @param list<mixed>                        $content
     * @param callable(BlockField, mixed): mixed $callback
     *
     * @return list<mixed>
     */
    private function walk(array $content, callable $callback): array
    {
        foreach ($content as $index => $item) {
            $block = \is_array($item) && \is_string($item['type'] ?? null) ? $this->registry->find($item['type']) : null;

            if ($block instanceof BlockInterface && \is_array($item['data'] ?? null)) {
                $content[$index]['data'] = $this->walkFields($this->registry->getFields($block), $item['data'], $callback);
            }
        }

        return array_values($content);
    }

    /**
     * @param list<BlockField>                   $fields
     * @param array<mixed>                       $data
     * @param callable(BlockField, mixed): mixed $callback
     *
     * @return array<mixed>
     */
    private function walkFields(array $fields, array $data, callable $callback): array
    {
        foreach ($fields as $field) {
            $value = $data[$field->name] ?? null;

            if (BlockField::REPEATER === $field->type && \is_array($value)) {
                $data[$field->name] = array_map(
                    fn (mixed $row): mixed => \is_array($row) ? $this->walkFields($field->fields, $row, $callback) : $row,
                    $value,
                );
            } elseif ($this->resolvers->has($field->type) && \array_key_exists($field->name, $data)) {
                $data[$field->name] = $callback($field, $value);
            }
        }

        return $data;
    }
}
