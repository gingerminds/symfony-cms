<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\ContentBlock;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\ResolvedFormTypeInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The references of a content (file and media ids...) replaced by their entity: one query per
 * resolver for the whole content (BlockReferenceResolverInterface), the rows included. The
 * reference fields of a block are found once in its form (cached in `cache.system`). `resolve()`
 * serves the API (each entity normalized), `entities()` the previews. A missing entity gives null
 * (it is removed from a list); the stored content keeps its ids.
 */
class ContentReferenceResolver
{
    private const string ROWS = '*';

    /** @var array<string, list<array{path: list<string>, type: string, multiple: bool}>> */
    private array $fields = [];

    /**
     * @param ServiceProviderInterface<BlockReferenceResolverInterface> $resolvers form type => resolver
     */
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly ServiceProviderInterface $resolvers,
        private readonly CacheInterface $cache,
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
        $ids = [];
        $this->walk($content, static function (string $type, bool $multiple, mixed $value) use (&$ids): mixed {
            $values = $multiple && \is_array($value) ? $value : [$value];
            $ids[$type] = [...$ids[$type] ?? [], ...array_filter($values, self::isId(...))];

            return $value;
        });

        $loaded = [];

        foreach ($ids as $type => $typeIds) {
            $loaded[$type] = [] === $typeIds ? [] : $this->resolvers->get($type)->load(array_values(array_unique($typeIds)));
        }

        return $this->walk($content, function (string $type, bool $multiple, mixed $value) use ($loaded, $normalize): mixed {
            $find = function (mixed $id) use ($type, $loaded, $normalize): mixed {
                $entity = self::isId($id) ? $loaded[$type][(string) $id] ?? null : null;

                return null !== $entity && $normalize ? $this->resolvers->get($type)->normalize($entity) : $entity;
            };

            if ($multiple) {
                return array_values(array_filter(array_map($find, \is_array($value) ? $value : []), static fn (mixed $entity): bool => null !== $entity));
            }

            return $find($value);
        });
    }

    /**
     * Applies $callback to the value of each reference field of the blocks.
     *
     * @param list<mixed>                          $content
     * @param callable(string, bool, mixed): mixed $callback resolver type, multiple, value
     *
     * @return list<mixed>
     */
    private function walk(array $content, callable $callback): array
    {
        foreach ($content as $index => $item) {
            $block = \is_array($item) && \is_string($item['type'] ?? null) ? $this->registry->find($item['type']) : null;

            if ($block instanceof ContentBlock && \is_array($item['data'] ?? null)) {
                $data = $item['data'];

                foreach ($this->referenceFields($block) as $field) {
                    $data = self::apply($data, $field['path'], static fn (mixed $value): mixed => $callback($field['type'], $field['multiple'], $value));
                }

                $content[$index]['data'] = $data;
            }
        }

        return array_values($content);
    }

    /**
     * @param array<mixed>           $data
     * @param list<string>           $path
     * @param callable(mixed): mixed $callback
     *
     * @return array<mixed>
     */
    private static function apply(array $data, array $path, callable $callback): array
    {
        $name = array_shift($path);

        if (self::ROWS === $name) {
            return array_map(static fn (mixed $row): mixed => \is_array($row) ? self::apply($row, $path, $callback) : $row, $data);
        }

        if (null === $name || !\array_key_exists($name, $data)) {
            return $data;
        }

        if ([] === $path) {
            $data[$name] = $callback($data[$name]);
        } elseif (\is_array($data[$name])) {
            $data[$name] = self::apply($data[$name], $path, $callback);
        }

        return $data;
    }

    /**
     * The fields of a block handled by a resolver: their path (`*` for the rows of a collection).
     *
     * @return list<array{path: list<string>, type: string, multiple: bool}>
     */
    private function referenceFields(ContentBlock $block): array
    {
        return $this->fields[$block->formType] ??= $this->cache->get(
            'gingerminds_cms.block_references.' . hash('xxh128', $block->formType),
            fn (): array => $this->collect($this->registry->createForm($block), []),
        );
    }

    /**
     * @param FormInterface<mixed> $form
     * @param list<string>         $path
     *
     * @return list<array{path: list<string>, type: string, multiple: bool}>
     */
    private function collect(FormInterface $form, array $path): array
    {
        $fields = [];

        foreach ($form as $name => $child) {
            $childPath = [...$path, $name];
            $config = $child->getConfig();
            $type = $this->resolverType($child);
            $prototype = $config->getAttribute('prototype');

            if (null !== $type) {
                $fields[] = ['path' => $childPath, 'type' => $type, 'multiple' => true === $config->getOption('multiple', false)];
            } elseif ($prototype instanceof FormInterface) {
                $fields = [...$fields, ...$this->collect($prototype, [...$childPath, self::ROWS])];
            } elseif ($config->getCompound()) {
                $fields = [...$fields, ...$this->collect($child, $childPath)];
            }
        }

        return $fields;
    }

    /**
     * The resolver of a field: its form type or a parent one.
     *
     * @param FormInterface<mixed> $form
     */
    private function resolverType(FormInterface $form): ?string
    {
        for ($type = $form->getConfig()->getType(); $type instanceof ResolvedFormTypeInterface; $type = $type->getParent()) {
            if ($this->resolvers->has($type->getInnerType()::class)) {
                return $type->getInnerType()::class;
            }
        }

        return null;
    }

    /**
     * @phpstan-assert-if-true int|non-empty-string $value
     */
    private static function isId(mixed $value): bool
    {
        return \is_int($value) || (\is_string($value) && '' !== $value);
    }
}
