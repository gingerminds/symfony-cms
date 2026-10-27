<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The content blocks by key (BlockPass): every block for the stored content, the enabled ones
 * (`gingerminds_cms.blocks.disabled`) for the picker. A disabled block is only hidden from the
 * picker: the pages using it keep working.
 */
class BlockRegistry
{
    /** @var array<string, list<BlockField>> */
    private array $fields = [];

    /**
     * @param ServiceProviderInterface<BlockInterface>          $blocks       block key => block
     * @param ServiceProviderInterface<BlockExtensionInterface> $extensions   service id => extension
     * @param array<string, list<string>>                       $extensionIds block key => extension ids, by priority
     * @param list<string>                                      $disabled     keys hidden from the picker
     * @param array<string, int>                                $order        block key => picker weight
     */
    public function __construct(
        private readonly ServiceProviderInterface $blocks,
        private readonly ServiceProviderInterface $extensions,
        private readonly array $extensionIds = [],
        private readonly array $disabled = [],
        private readonly array $order = [],
    ) {
    }

    /**
     * Every block, enabled or not.
     *
     * @return array<string, BlockInterface>
     */
    public function all(): array
    {
        $blocks = [];

        foreach (array_keys($this->blocks->getProvidedServices()) as $key) {
            $blocks[$key] = $this->blocks->get($key);
        }

        return $blocks;
    }

    /**
     * The blocks of the picker: enabled, by weight then key.
     *
     * @return list<BlockInterface>
     */
    public function active(): array
    {
        $blocks = array_filter($this->all(), fn (BlockInterface $block): bool => !\in_array($block::getKey(), $this->disabled, true));
        uasort($blocks, fn (BlockInterface $a, BlockInterface $b): int => [$this->weight($a), $a::getKey()] <=> [$this->weight($b), $b::getKey()]);

        return array_values($blocks);
    }

    public function has(string $key): bool
    {
        return $this->blocks->has($key);
    }

    public function find(string $key): ?BlockInterface
    {
        return $this->blocks->has($key) ? $this->blocks->get($key) : null;
    }

    /**
     * The fields of a block, its extensions applied.
     *
     * @return list<BlockField>
     */
    public function getFields(BlockInterface $block): array
    {
        $key = $block::getKey();

        if (!isset($this->fields[$key])) {
            $fields = $block->getFields();

            foreach ($this->extensionIds[$key] ?? [] as $id) {
                $fields = $this->extensions->get($id)->buildFields($fields, $block);
            }

            $this->fields[$key] = $fields;
        }

        return $this->fields[$key];
    }

    /**
     * The default value of each field: the data of a new block, completed in the stored ones (a
     * field added after the pages were saved).
     *
     * @return array<string, mixed>
     */
    public function getDefaults(BlockInterface $block): array
    {
        $defaults = [];

        foreach ($this->getFields($block) as $field) {
            $defaults[$field->name] = $field->default;
        }

        return $defaults;
    }

    /**
     * Translation key of a field label (block domain): its own label, else
     * `block.<key>.field.<name>` (`<repeater>_<name>` for a repeater sub-field).
     */
    public function getFieldLabel(BlockInterface $block, BlockField $field, ?BlockField $repeater = null): string
    {
        $name = $repeater instanceof BlockField ? $repeater->name . '_' . $field->name : $field->name;

        return $field->label ?? 'block.' . $block::getKey() . '.field.' . $name;
    }

    private function weight(BlockInterface $block): int
    {
        return $this->order[$block::getKey()] ?? $block->getOrder();
    }
}
