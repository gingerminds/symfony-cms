<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Block;

use Gingerminds\CmsBundle\Block\BlockConstraints;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The data of a block (or of a repeater row: `fields` and `repeater` options), one form field per
 * BlockField: text, textarea, WYSIWYG, select, switch, repeater (a collection of rows, Stimulus
 * `gm-cms-repeater`). Validated by BlockConstraints.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class BlockDataType extends AbstractType
{
    public function __construct(
        private readonly BlockRegistry $registry,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var BlockInterface $block */
        $block = $options['block'];

        foreach ($this->fields($options) as $field) {
            $builder->add($field->name, ...$this->child($block, $field, $options['repeater']));

            if (BlockField::REPEATER === $field->type) {
                // The rows are submitted in display order (drag & drop): renumbered before the
                // collection resize, they keep it.
                $builder->get($field->name)->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
                    $rows = $event->getData();
                    $event->setData(\is_array($rows) ? array_values($rows) : $rows);
                }, 10);
            }
        }
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        /** @var BlockInterface $block */
        $block = $options['block'];

        foreach ($this->fields($options) as $field) {
            if (BlockField::REPEATER !== $field->type) {
                continue;
            }

            $repeater = $view[$field->name];
            $repeater->vars['add_label'] = $field->addLabel ?? 'blocks.action.add_row';
            $repeater->vars['add_label_domain'] = null !== $field->addLabel ? $block->getTranslationDomain() : GingermindsCmsBundle::TRANSLATION_DOMAIN;
            $repeater->vars['item_label'] = $field->itemLabel ?? 'blocks.message.row';
            $repeater->vars['item_label_domain'] = null !== $field->itemLabel ? $block->getTranslationDomain() : GingermindsCmsBundle::TRANSLATION_DOMAIN;
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setRequired('block')
            ->setAllowedTypes('block', BlockInterface::class)
            ->setDefaults([
                'fields' => null,
                'repeater' => null,
                'label' => false,
            ])
            ->setAllowedTypes('fields', ['null', BlockField::class . '[]'])
            ->setAllowedTypes('repeater', ['null', BlockField::class]);
    }

    public function getBlockPrefix(): string
    {
        return 'gm_cms_block_data';
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return list<BlockField>
     */
    private function fields(array $options): array
    {
        /** @var list<BlockField>|null $fields */
        $fields = $options['fields'];

        /** @var BlockInterface $block */
        $block = $options['block'];

        return $fields ?? $this->registry->getFields($block);
    }

    /**
     * The form type and options of a field.
     *
     * @return array{class-string<FormTypeInterface<mixed>>, array<string, mixed>}
     */
    private function child(BlockInterface $block, BlockField $field, ?BlockField $repeater): array
    {
        $options = [
            'label' => $this->registry->getFieldLabel($block, $field, $repeater),
            'translation_domain' => $block->getTranslationDomain(),
            'required' => $field->required,
            'help' => $field->help,
            'size' => $field->size,
            'constraints' => BlockConstraints::forField($field),
        ];

        return match ($field->type) {
            BlockField::TEXTAREA => [TextareaType::class, $options + ['attr' => ['rows' => 4]]],
            BlockField::WYSIWYG => [WysiwygType::class, $options + ['preset' => $field->preset, 'rows' => $field->rows]],
            BlockField::SELECT => [ChoiceType::class, $options + [
                'choices' => array_combine(array_values($field->choices), array_map(strval(...), array_keys($field->choices))),
                'placeholder' => $field->required ? null : '',
            ]],
            BlockField::TOGGLE => [CheckboxType::class, ['label_attr' => ['class' => 'checkbox-switch']] + $options],
            BlockField::REPEATER => [CollectionType::class, $options + [
                'entry_type' => self::class,
                'entry_options' => ['block' => $block, 'fields' => $field->fields, 'repeater' => $field],
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'block_prefix' => 'gm_cms_block_repeater',
                'error_bubbling' => false,
            ]],
            default => [TextType::class, $options],
        };
    }
}
