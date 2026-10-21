<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;

class MenuController extends AbstractCrudController
{
    public function __construct(
        CrudContext $context,
        protected readonly SiteContext $siteContext,
    ) {
        parent::__construct($context);
    }

    protected function getResourceName(): string
    {
        return 'menu';
    }

    // The site is set before the validation: a menu cannot be created without current site.
    protected function createEntity(): object
    {
        $menu = parent::createEntity();

        if ($menu instanceof MenuInterface && $this->siteContext->has()) {
            $menu->setSite($this->siteContext->site());
        }

        return $menu;
    }
}
