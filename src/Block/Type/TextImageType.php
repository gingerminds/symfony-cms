<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A title, a rich text and an image, on the left or on the right.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'text_image',
    preview: '@GingermindsCms/block/text_image/preview.html.twig',
    icon: 'bi-image',
    order: 100,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class TextImageType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()], 'size' => 'xl'])
            ->add('image_position', CheckboxType::class, ['required' => false, 'help' => 'block.text_image.help.image_position', 'label_attr' => ['class' => 'checkbox-switch']])
            ->add('text', WysiwygType::class, ['required' => false, 'rows' => 8, 'size' => 'xl'])
            ->add('image', FilePickerType::class, ['constraints' => [new NotBlank()], 'as_id' => true, 'accept' => ['image/*'], 'size' => 'xl']);
    }
}
