<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;

/**
 * Implements SiteMainMenuInterface: nullable `sites.main_menu_id`, emptied when the menu is deleted.
 */
trait SiteMainMenuTrait
{
    #[ORM\ManyToOne(targetEntity: MenuInterface::class)]
    #[ORM\JoinColumn(name: 'main_menu_id', nullable: true, onDelete: 'SET NULL')]
    protected ?MenuInterface $mainMenu = null;

    public function getMainMenu(): ?MenuInterface
    {
        return $this->mainMenu;
    }

    public function setMainMenu(?MenuInterface $mainMenu): void
    {
        $this->mainMenu = $mainMenu;
    }
}
