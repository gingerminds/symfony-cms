<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Symfony\Component\Form\FormInterface;

/**
 * The stored shape of a content (`[{uid, type, data}]`), before its validation: the data of each
 * block is the one of its form submitted with it (BlockRegistry::submit()): the fields it no longer
 * has dropped, the new ones at their `empty_data`, toggles as booleans, empty texts null, rows as a
 * list, the WYSIWYG HTML sanitized again (the content is a hidden field: it can be forged). A block
 * of an unknown type, or a value that does not fit its field, is kept as is (ValidBlocks rejects
 * it): a save does not destroy it.
 */
class BlockContentNormalizer
{
    public function __construct(
        private readonly BlockRegistry $registry,
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

            if (\is_array($item) && $block instanceof ContentBlock && \is_array($item['data'] ?? null)) {
                $item = ['uid' => $item['uid'] ?? null, 'type' => $item['type'], 'data' => $this->normalizeData($block, $item['data'])];
            }

            $items[] = $item;
        }

        return $items;
    }

    /**
     * @param array<mixed> $data
     *
     * @return array<mixed>
     */
    public function normalizeData(ContentBlock $block, array $data): array
    {
        $normalized = self::data($this->registry->submit($block, $data), $data);

        return \is_array($normalized) ? $normalized : $data;
    }

    /**
     * The data of a submitted form, the submitted value of a field that could not be transformed
     * (an unknown choice...) kept for the validation to report it.
     *
     * @param FormInterface<mixed> $form
     */
    private static function data(FormInterface $form, mixed $submitted): mixed
    {
        $data = $form->getData();

        if (!$form->isSynchronized()) {
            return $submitted;
        }

        if (!$form->getConfig()->getCompound() || !\is_array($data)) {
            return $data;
        }

        $submitted = \is_array($submitted) ? $submitted : [];

        // The rows are renumbered by BlockCollectionType.
        if ($form->getConfig()->getType()->getInnerType() instanceof BlockCollectionType) {
            $submitted = array_values($submitted);
        }

        foreach ($form as $name => $child) {
            $data[$name] = self::data($child, $submitted[$name] ?? null);
        }

        return $data;
    }
}
