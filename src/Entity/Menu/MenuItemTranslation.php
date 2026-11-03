<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;

/**
 * The bundle MenuItemTranslation (`resources.menu_item_translation.entity` replaces it): a project
 * entity extends BaseMenuItemTranslation and restates these class-level attributes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'menu_item_translations')]
#[ORM\UniqueConstraint(name: 'menu_item_translations_menu_item_id_language_id_unique', columns: ['menu_item_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
class MenuItemTranslation extends BaseMenuItemTranslation
{
}
