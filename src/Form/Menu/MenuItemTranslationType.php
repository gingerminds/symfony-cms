<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuItemTranslation;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Fields of one language of a menu item. The description becomes a WYSIWYG at step 6.
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
            ->add('description', TextareaType::class, [
                'label' => 'menu_item.field.description',
                'required' => false,
                'size' => 'xl',
                'attr' => ['rows' => 5],
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
