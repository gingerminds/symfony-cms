<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Security\Voter;

use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

/**
 * `view|edit|delete menu_items` permissions.
 */
class MenuItemVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'menu_item';
    }

    protected function getSubjectClass(): string
    {
        return MenuItemInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'menu_items';
    }
}
