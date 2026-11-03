<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A title, a text and cards (title, description, image).
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'cards',
    preview: '@GingermindsCms/block/cards/preview.html.twig',
    icon: 'bi-grid-3x2-gap',
    order: 300,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class CardsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('text', WysiwygType::class, ['required' => false, 'rows' => 6, 'size' => 'xl'])
            ->add('cards', BlockCollectionType::class, ['entry_type' => CardType::class, 'add_label' => 'block.cards.add', 'item_label' => 'block.cards.item']);
    }
}
