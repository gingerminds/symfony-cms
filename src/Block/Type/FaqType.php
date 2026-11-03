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
 * Frequently asked questions: a title, questions and their answer.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'faq',
    preview: '@GingermindsCms/block/faq/preview.html.twig',
    icon: 'bi-question-lg',
    order: 500,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class FaqType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('items', BlockCollectionType::class, ['entry_type' => FaqItemType::class, 'add_label' => 'block.faq.add', 'item_label' => 'block.faq.item']);
    }
}
