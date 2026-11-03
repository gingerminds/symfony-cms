<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Page;

use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Form\Block\BlockCanvasType;
use Gingerminds\CmsBundle\Form\Type\SlugType;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One language of a page. The slug is slugified from the title (SlugType), its
 * input group starts with the category path (PageType). The hook is a WYSIWYG, the content is made
 * of blocks (BlockCanvasType).
 *
 * @extends AbstractType<PageTranslation>
 */
class PageTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'page.field.title',
                'size' => 'md',
            ])
            ->add('slug', SlugType::class, [
                'label' => 'page.field.slug',
                'help' => 'page.help.slug',
                'source' => 'title',
                'overwrite' => $options['slug_overwrite'],
                'size' => 'xl',
                'input_prefix' => '/',
            ])
            ->add('hook', WysiwygType::class, [
                'label' => 'page.field.hook',
                'required' => false,
                'size' => 'xl',
                'preset' => 'minimal',
                'rows' => 8,
            ])
            ->add('content', BlockCanvasType::class, [
                'label' => 'page.field.content',
            ])
            ->add('mainVisual', FilePickerType::class, [
                'label' => 'page.field.main_visual',
                'help' => 'page.help.translation_visual',
                'accept' => ['image/*'],
                'required' => false,
                'size' => 'md',
            ])
            ->add('thumbnail', FilePickerType::class, [
                'label' => 'page.field.thumbnail',
                'help' => 'page.help.translation_visual',
                'accept' => ['image/*'],
                'required' => false,
                'size' => 'md',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => PageTranslation::class,
                'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
                'slug_overwrite' => true,
            ])
            ->setAllowedTypes('slug_overwrite', 'bool');
    }
}
