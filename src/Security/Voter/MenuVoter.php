<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Security\Voter;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

/**
 * `view|edit|delete menus` permissions.
 */
class MenuVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'menu';
    }

    protected function getSubjectClass(): string
    {
        return MenuInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'menus';
    }
}
