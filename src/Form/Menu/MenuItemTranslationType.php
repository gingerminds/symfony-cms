<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuItemTranslation;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fields of one language of a menu item, the description in a WYSIWYG.
 *
 * @extends AbstractType<MenuItemTranslation>
 */
class MenuItemTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'menu_item.field.name',
                'size' => 'lg',
            ])
            ->add('url', TextType::class, [
                'label' => 'menu_item.field.url',
                'required' => false,
                'size' => 'lg',
            ])
            ->add('description', WysiwygType::class, [
                'label' => 'menu_item.field.description',
                'required' => false,
                'size' => 'lg',
                'preset' => 'minimal',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => MenuItemTranslation::class,
            'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
        ]);
    }
}
