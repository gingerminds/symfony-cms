<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;

/**
 * `GET /api/menus` and `/api/menus/{id}` are public: the menus of the current site with their
 * active item tree, in the current language.
 */
#[ApiResource(
    shortName: 'Menu',
    operations: [
        new GetCollection(normalizationContext: ['groups' => [BaseMenu::GROUP_LIST], 'skip_null_values' => false]),
        new Get(),
    ],
    normalizationContext: ['groups' => [BaseMenu::GROUP_READ], 'skip_null_values' => false],
    paginationClientItemsPerPage: true,
    provider: 'gingerminds_cms.api.provider.menu',
)]
#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menus')]
class Menu extends BaseMenu
{
}
