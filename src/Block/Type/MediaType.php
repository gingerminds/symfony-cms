<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A title and a file of any type (a document to download...).
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(
    key: 'media',
    preview: '@GingermindsCms/block/media/preview.html.twig',
    icon: 'bi-file-earmark-richtext',
    order: 150,
    translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN,
)]
class MediaType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('file', FilePickerType::class, ['constraints' => [new NotBlank()], 'as_id' => true, 'accept' => [], 'size' => 'xl']);
    }
}
