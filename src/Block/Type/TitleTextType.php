<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A title and a rich text.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'title_text',
    preview: '@GingermindsCms/block/title_text/preview.html.twig',
    icon: 'bi-type',
    order: 0,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class TitleTextType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('text', WysiwygType::class, ['required' => false, 'preset' => 'extended', 'rows' => 8, 'size' => 'xl']);
    }
}
