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
 * A title and slides (images).
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'slider',
    preview: '@GingermindsCms/block/slider/preview.html.twig',
    icon: 'bi-images',
    order: 350,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class SliderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('slides', BlockCollectionType::class, ['entry_type' => SlideType::class, 'add_label' => 'block.slider.add', 'item_label' => 'block.slider.item']);
    }
}
