<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Security\Voter;

use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

/**
 * `view|edit|delete pages` permissions.
 */
class PageVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'page';
    }

    protected function getSubjectClass(): string
    {
        return PageInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'pages';
    }
}
