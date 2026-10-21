<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Name and prefix of one language of a page category. The prefix is slugified from the name
 * (Stimulus `gm-cms-slug-sync`); `slug_overwrite`: whether a filled prefix is replaced too.
 *
 * @extends AbstractType<PageCategoryTranslation>
 */
class PageCategoryTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'page_category.field.name',
                'size' => 'lg',
            ])
            ->add('prefix', TextType::class, [
                'label' => 'page_category.field.prefix',
                'required' => false,
                'size' => 'lg',
                'help' => 'page_category.help.prefix',
                'input_prefix' => '/',
                'input_suffix' => '/',
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $prefix = $view['prefix'];
        $prefix->vars['attr'] = [
            ...$prefix->vars['attr'],
            'data-controller' => 'gm-cms-slug-sync',
            'data-gm-cms-slug-sync-source-value' => '#' . $view['name']->vars['id'],
            'data-gm-cms-slug-sync-overwrite-value' => $options['slug_overwrite'] ? 'true' : 'false',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => PageCategoryTranslation::class,
                'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
                'slug_overwrite' => true,
            ])
            ->setAllowedTypes('slug_overwrite', 'bool');
    }
}
