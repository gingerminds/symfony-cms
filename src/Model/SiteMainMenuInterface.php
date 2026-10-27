<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * A site with a main menu, chosen in its admin form (SiteMainMenuTrait).
 */
interface SiteMainMenuInterface extends SiteInterface
{
    public function getMainMenu(): ?MenuInterface;

    public function setMainMenu(?MenuInterface $mainMenu): void;
}
