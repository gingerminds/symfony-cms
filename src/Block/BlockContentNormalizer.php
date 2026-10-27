<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Gingerminds\CmsBundle\Wysiwyg\WysiwygSanitizer;

/**
 * The stored shape of a content (`[{uid, type, data}]`), before its validation: the data of each
 * block keeps the fields it declares now (the removed ones are dropped, the new ones get their
 * default), toggles become booleans, empty strings null, repeater rows a list, the WYSIWYG HTML
 * is sanitized again (the content is a hidden field: it can be forged). A block of an unknown type
 * is kept as is (ValidBlocks rejects it): a save does not destroy it.
 */
class BlockContentNormalizer
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly WysiwygSanitizer $sanitizer,
    ) {
    }

    /**
     * @param array<mixed> $content
     *
     * @return list<mixed>
     */
    public function normalize(array $content): array
    {
        $items = [];

        foreach ($content as $item) {
            $block = \is_array($item) && \is_string($item['type'] ?? null) ? $this->registry->find($item['type']) : null;

            if (\is_array($item) && $block instanceof BlockInterface) {
                $item = [
                    'uid' => $item['uid'] ?? null,
                    'type' => $item['type'],
                    'data' => \is_array($item['data'] ?? null) ? $this->normalizeData($block, $item['data']) : $item['data'] ?? null,
                ];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<string, mixed>
     */
    public function normalizeData(BlockInterface $block, array $data): array
    {
        return $this->normalizeFields($this->registry->getFields($block), $data);
    }

    /**
     * @param list<BlockField> $fields
     * @param array<mixed>     $data
     *
     * @return array<string, mixed>
     */
    private function normalizeFields(array $fields, array $data): array
    {
        $normalized = [];

        foreach ($fields as $field) {
            $normalized[$field->name] = $this->normalizeValue($field, \array_key_exists($field->name, $data) ? $data[$field->name] : $field->default);
        }

        return $normalized;
    }

    /**
     * Media ids as integers (a numeric string from a forged content too), a list when multiple.
     */
    private function mediaIds(BlockField $field, mixed $value): mixed
    {
        $id = static fn (mixed $id): mixed => \is_string($id) && ctype_digit($id) ? (int) $id : $id;

        return match (true) {
            $field->multiple && \is_array($value) => array_values(array_map($id, $value)),
            '' === $value => null,
            default => $id($value),
        };
    }

    private function normalizeValue(BlockField $field, mixed $value): mixed
    {
        return match (true) {
            BlockField::TOGGLE === $field->type => \is_scalar($value) && filter_var($value, \FILTER_VALIDATE_BOOLEAN),
            BlockField::REPEATER === $field->type && \is_array($value) => array_map(
                fn (mixed $row): mixed => \is_array($row) ? $this->normalizeFields($field->fields, $row) : $row,
                array_values($value),
            ),
            BlockField::WYSIWYG === $field->type && \is_string($value) => $this->sanitizer->sanitize($value),
            BlockField::MEDIA === $field->type => $this->mediaIds($field, $value),
            '' === $value => null,
            default => $value,
        };
    }
}
