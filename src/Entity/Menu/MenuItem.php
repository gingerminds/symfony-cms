<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;

#[ORM\Entity(repositoryClass: MenuItemRepository::class)]
#[ORM\Table(name: 'menu_items')]
class MenuItem extends BaseMenuItem
{
}
