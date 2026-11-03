<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Component\Form\FormTypeInterface;

/**
 * A registered content block: its form type and its #[AsContentBlock] metadata.
 */
final readonly class ContentBlock
{
    public string $label;

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     */
    public function __construct(
        public string $key,
        public string $formType,
        public string $previewTemplate,
        public string $icon,
        public int $order,
        ?string $label,
        public string $translationDomain,
    ) {
        $this->label = $label ?? 'block.' . $key . '.label';
    }

    /**
     * @param class-string<FormTypeInterface<mixed>> $formType
     */
    public static function fromAttribute(string $formType, AsContentBlock $block): self
    {
        return new self($block->key, $formType, $block->preview, $block->icon, $block->order, $block->label, $block->translationDomain);
    }

    /**
     * The `label_format` of its fields.
     */
    public function fieldLabelFormat(): string
    {
        return 'block.' . $this->key . '.field.%name%';
    }
}
