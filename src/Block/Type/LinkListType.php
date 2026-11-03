<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A title and links (label, URL, image).
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'link_list',
    preview: '@GingermindsCms/block/link_list/preview.html.twig',
    icon: 'bi-link-45deg',
    order: 400,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class LinkListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('links', BlockCollectionType::class, ['entry_type' => LinkType::class, 'add_label' => 'block.link_list.add', 'item_label' => 'block.link_list.item']);
    }
}
