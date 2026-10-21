<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

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

    /**
     * For `GET /api/sites`: the id does not load the menu (a menu of another site is filtered out).
     */
    #[Groups([BaseSite::GROUP_LIST, BaseSite::GROUP_READ])]
    #[SerializedName('main_menu_id')]
    public function getMainMenuId(): ?int
    {
        return $this->mainMenu?->getId();
    }
}
