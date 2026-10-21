<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;

#[ORM\Entity(repositoryClass: MenuRepository::class)]
#[ORM\Table(name: 'menus')]
class Menu extends BaseMenu
{
}
