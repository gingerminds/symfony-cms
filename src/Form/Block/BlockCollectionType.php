<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Block;

use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Rows of a content block (a repeater): a collection of `entry_type`, collapsible, added, removed
 * and reordered by drag & drop (Stimulus `gm-cms-repeater`). `add_label` / `item_label`: the add
 * button and the row title ("<label> <number>"), translation keys of the block domain (the
 * GingermindsCms defaults otherwise). The stored rows the entry no longer has fields for are ignored.
 *
 *     $builder->add('cards', BlockCollectionType::class, ['entry_type' => CardType::class, 'item_label' => 'block.cards.item']);
 *
 * @extends AbstractType<list<array<string, mixed>>>
 */
class BlockCollectionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // The rows are submitted in display order (drag & drop): renumbered before the resize, they keep it.
        $builder->addEventListener(FormEvents::PRE_SUBMIT, static function (FormEvent $event): void {
            $rows = $event->getData();
            $event->setData(\is_array($rows) ? array_values($rows) : $rows);
        }, 10);
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $domain = $view->vars['translation_domain'];
        $view->vars['add_label'] = $options['add_label'] ?? 'blocks.action.add_row';
        $view->vars['add_label_domain'] = null !== $options['add_label'] ? $domain : GingermindsCmsBundle::TRANSLATION_DOMAIN;
        $view->vars['item_label'] = $options['item_label'] ?? 'blocks.message.row';
        $view->vars['item_label_domain'] = null !== $options['item_label'] ? $domain : GingermindsCmsBundle::TRANSLATION_DOMAIN;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'allow_add' => true,
                'allow_delete' => true,
                'prototype' => true,
                'error_bubbling' => false,
                'required' => false,
                'size' => 'xl',
                'add_label' => null,
                'item_label' => null,
            ])
            ->setAllowedTypes('add_label', ['null', 'string'])
            ->setAllowedTypes('item_label', ['null', 'string'])
            ->addNormalizer('entry_options', static fn (Options $options, array $entryOptions): array => ['label' => false, 'allow_extra_fields' => true, ...$entryOptions]);
    }

    public function getParent(): string
    {
        return CollectionType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gm_cms_block_repeater';
    }
}
