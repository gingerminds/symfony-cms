<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<MenuInterface>
 */
class MenuType extends AbstractType
{
    public function __construct(
        protected readonly ResourceRegistry $resources,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('code', TextType::class, [
            'label' => 'menu.field.code',
            'size' => 'lg',
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->resources->getEntityClass('menu'),
            'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
        ]);
    }
}
