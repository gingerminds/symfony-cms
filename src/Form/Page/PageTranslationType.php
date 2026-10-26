<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Page;

use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * One language of a page. The slug is slugified from the title (Stimulus `gm-cms-slug-sync`), its
 * input group starts with the category path (PageType). The hook becomes a WYSIWYG at step 6.
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
            ->add('slug', TextType::class, [
                'label' => 'page.field.slug',
                'help' => 'page.help.slug',
                'required' => false,
                'size' => 'xl',
                'input_prefix' => '/',
            ])
            ->add('hook', TextareaType::class, [
                'label' => 'page.field.hook',
                'required' => false,
                'size' => 'xl',
                'attr' => ['rows' => 4],
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

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $slug = $view['slug'];
        $slug->vars['attr'] = [
            ...$slug->vars['attr'],
            'data-controller' => 'gm-cms-slug-sync',
            'data-gm-cms-slug-sync-source-value' => '#' . $view['title']->vars['id'],
            'data-gm-cms-slug-sync-overwrite-value' => $options['slug_overwrite'] ? 'true' : 'false',
        ];
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
