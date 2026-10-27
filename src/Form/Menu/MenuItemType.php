<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Model\TreeNodes;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * `menu` option: the menu of the item, whose tree gives the parent choices (the item and its
 * descendants excluded).
 *
 * @extends AbstractType<MenuItemInterface>
 */
class MenuItemType extends AbstractType
{
    public function __construct(
        protected readonly ResourceRegistry $resources,
        protected readonly MenuItemRepository $items,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var MenuInterface $menu */
        $menu = $options['menu'];
        $item = $builder->getData();
        [$choices, $depths] = TreeNodes::parentChoices($this->items->findByMenu($menu), $item instanceof MenuItemInterface ? $item : null);

        $builder
            ->add('code', TextType::class, [
                'label' => 'menu_item.field.code',
                'size' => 'lg',
            ])
            ->add('active', CheckboxType::class, [
                'label' => 'menu_item.field.is_active',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch'],
                'size' => 'xl',
            ])
            ->add('parent', EntityType::class, [
                'label' => 'menu_item.field.parent',
                'class' => $this->resources->getEntityClass('menu_item'),
                'choices' => $choices,
                'choice_label' => static fn (MenuItemInterface $choice): string => str_repeat('— ', $depths[(int) $choice->getId()] ?? 0) . $choice,
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'menu_item.placeholder.parent',
                'size' => 'xl',
            ]);

        foreach (['targetBlank' => 'is_target_blank', 'noReferrer' => 'is_no_referrer', 'noOpener' => 'is_no_opener', 'noFollow' => 'is_no_follow'] as $property => $name) {
            $builder->add($property, CheckboxType::class, [
                'label' => 'menu_item.field.' . $name,
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch'],
                'size' => 'xl',
            ]);
        }

        $builder->add('translations', TranslationsType::class, [
            'label' => false,
            'entry_type' => MenuItemTranslationType::class,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'data_class' => $this->resources->getEntityClass('menu_item'),
                'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
            ])
            ->setRequired('menu')
            ->setAllowedTypes('menu', MenuInterface::class);
    }
}
