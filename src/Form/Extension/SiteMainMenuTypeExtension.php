<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Extension;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Model\SiteMainMenuInterface;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * "Main menu" field of the site form, among the menus of that site (new site: none yet),
 * when the site entity implements SiteMainMenuInterface.
 */
final class SiteMainMenuTypeExtension extends AbstractTypeExtension
{
    public const string FIELD = 'main_menu';

    public function __construct(
        private readonly MenuRepository $menus,
    ) {
    }

    public static function getExtendedTypes(): iterable
    {
        return [SiteType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        if (!is_a((string) $options['data_class'], SiteMainMenuInterface::class, true)) {
            return;
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $site = $event->getData();
            $menus = $site instanceof SiteMainMenuInterface && null !== $site->getId() ? $this->menus->findBySite($site) : [];

            $event->getForm()->add(self::FIELD, EntityType::class, [
                'label' => 'site.field.main_menu',
                'help' => [] === $menus ? 'site.help.main_menu_empty' : null,
                'class' => $this->menus->getEntityClass(),
                'choices' => $menus,
                'choice_label' => static fn (MenuInterface $menu): string => (string) $menu->getCode(),
                'choice_translation_domain' => false,
                'property_path' => 'mainMenu',
                'required' => false,
                'placeholder' => 'site.placeholder.main_menu',
                'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
                'size' => 'xl',
            ]);
        });
    }
}
