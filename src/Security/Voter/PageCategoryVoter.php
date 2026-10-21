<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Security\Voter;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;

/**
 * `view|edit|delete page_categories` permissions.
 */
class PageCategoryVoter extends AbstractResourceVoter
{
    protected function getResourceName(): string
    {
        return 'page_category';
    }

    protected function getSubjectClass(): string
    {
        return PageCategoryInterface::class;
    }

    protected function getPermissionName(): string
    {
        return 'page_categories';
    }
}
