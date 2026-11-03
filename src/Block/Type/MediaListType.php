<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * A title and medias of the media library with a subtitle.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'media_list',
    preview: '@GingermindsCms/block/media_list/preview.html.twig',
    icon: 'bi-collection-play',
    order: 450,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class MediaListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('items', BlockCollectionType::class, ['entry_type' => MediaListItemType::class, 'add_label' => 'block.media_list.add', 'item_label' => 'block.media_list.item']);
    }
}
