<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Contracts\Service\ServiceProviderInterface;

/**
 * The content blocks by key: the #[AsContentBlock] form types (tag `gingerminds_cms.block`). Every
 * block for the stored content, the enabled ones (`gingerminds_cms.blocks.disabled`) for the
 * picker: a disabled block is only hidden from it, the pages using it keep working.
 */
class BlockRegistry
{
    public const string TAG = 'gingerminds_cms.block';

    /** @var array<string, ContentBlock>|null */
    private ?array $blocks = null;

    /** @var array<string, array<mixed>> */
    private array $defaults = [];

    /**
     * @param ServiceProviderInterface<FormTypeInterface<mixed>> $types    the block form types
     * @param list<string>                                       $disabled keys hidden from the picker
     * @param array<string, int>                                 $order    block key => picker weight
     */
    public function __construct(
        private readonly ServiceProviderInterface $types,
        private readonly FormFactoryInterface $formFactory,
        private readonly array $disabled = [],
        private readonly array $order = [],
    ) {
    }

    /**
     * Every block, enabled or not.
     *
     * @return array<string, ContentBlock>
     */
    public function all(): array
    {
        if (null !== $this->blocks) {
            return $this->blocks;
        }

        $blocks = [];

        foreach ($this->types->getProvidedServices() as $type) {
            /** @var class-string<FormTypeInterface<mixed>> $class */
            $class = ltrim($type, '?');
            $block = ContentBlock::fromAttribute($class, self::attribute($class));
            $other = $blocks[$block->key] ?? null;

            // The same class twice: a bundle block service replaced by the project one.
            if ($other instanceof ContentBlock && $other->formType !== $class) {
                throw new \LogicException(\sprintf(
                    'The content blocks "%s" and "%s" have the same key "%s": replace the service of the first one instead.',
                    $other->formType,
                    $class,
                    $block->key,
                ));
            }

            $blocks[$block->key] = $block;
        }

        return $this->blocks = $blocks;
    }

    /**
     * The blocks of the picker: enabled, by weight then key.
     *
     * @return list<ContentBlock>
     */
    public function active(): array
    {
        $blocks = array_filter($this->all(), fn (ContentBlock $block): bool => !\in_array($block->key, $this->disabled, true));
        uasort($blocks, fn (ContentBlock $a, ContentBlock $b): int => [$this->weight($a), $a->key] <=> [$this->weight($b), $b->key]);

        return array_values($blocks);
    }

    public function find(string $key): ?ContentBlock
    {
        return $this->all()[$key] ?? null;
    }

    /**
     * The form of a block (named `data`), its fields labelled `block.<key>.field.<name>` in its
     * domain; the stored fields it no longer has are ignored.
     *
     * @param array<mixed>|null    $data
     * @param array<string, mixed> $options
     *
     * @return FormInterface<array<string, mixed>|null>
     */
    public function createForm(ContentBlock $block, ?array $data = null, array $options = []): FormInterface
    {
        return $this->formFactory->createNamed('data', $block->formType, $data, [
            'label' => false,
            'label_format' => $block->fieldLabelFormat(),
            'translation_domain' => $block->translationDomain,
            'allow_extra_fields' => true,
            ...$options,
        ]);
    }

    /**
     * The form of a block submitted with stored data: a field missing from it gets its `empty_data`.
     *
     * @param array<mixed> $data
     *
     * @return FormInterface<array<string, mixed>|null>
     */
    public function submit(ContentBlock $block, array $data): FormInterface
    {
        $form = $this->createForm($block, null, ['csrf_protection' => false]);
        // Form::submit() casts the scalars to strings and a false toggle to unchecked.
        $form->submit($data);

        return $form;
    }

    /**
     * The data of an empty block: the `empty_data` of each field. A stored block completed with
     * it gets the fields added since it was saved.
     *
     * @return array<mixed>
     */
    public function defaults(ContentBlock $block): array
    {
        if (!isset($this->defaults[$block->key])) {
            $data = $this->submit($block, [])->getData();
            $this->defaults[$block->key] = \is_array($data) ? $data : [];
        }

        return $this->defaults[$block->key];
    }

    /**
     * The #[AsContentBlock] of a form type, its parents included.
     *
     * @param class-string $class
     */
    public static function attribute(string $class): AsContentBlock
    {
        for ($reflection = new \ReflectionClass($class); false !== $reflection; $reflection = $reflection->getParentClass()) {
            foreach ($reflection->getAttributes(AsContentBlock::class) as $attribute) {
                return $attribute->newInstance();
            }
        }

        throw new \LogicException(\sprintf('The content block "%s" has no #[%s].', $class, AsContentBlock::class));
    }

    private function weight(ContentBlock $block): int
    {
        return $this->order[$block->key] ?? $block->order;
    }
}
