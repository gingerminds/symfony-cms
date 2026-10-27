<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Controller\Menu\MenuController;
use Gingerminds\CmsBundle\Controller\Menu\MenuItemController;
use Gingerminds\CmsBundle\Controller\Page\PageCategoryController;
use Gingerminds\CmsBundle\Controller\Page\PageController;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;

/*
 * Admin controllers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.controller.admin.menu', MenuController::class)
        ->args([service('gingerminds_core.controller.context'), service('gingerminds_multisite.context.site')])
        ->tag('controller.service_arguments');
    $services->alias(MenuController::class, 'gingerminds_cms.controller.admin.menu')->public();

    $services->set('gingerminds_cms.controller.admin.menu_item', MenuItemController::class)
        ->args([service('gingerminds_core.controller.context')])
        ->tag('controller.service_arguments');
    $services->alias(MenuItemController::class, 'gingerminds_cms.controller.admin.menu_item')->public();

    $services->set('gingerminds_cms.controller.admin.page_category', PageCategoryController::class)
        ->args([service('gingerminds_core.controller.context'), service('gingerminds_multisite.context.site'), service('gingerminds_cms.page.url_syncer')])
        ->tag('controller.service_arguments');
    $services->alias(PageCategoryController::class, 'gingerminds_cms.controller.admin.page_category')->public();

    $services->set('gingerminds_cms.controller.admin.page', PageController::class)
        ->args([
            service('gingerminds_core.controller.context'),
            service('gingerminds_multisite.context.site'),
            service(PageCategoryRepository::class),
            service('state_machine.gingerminds_cms_publication'),
        ])
        ->tag('controller.service_arguments');
    $services->alias(PageController::class, 'gingerminds_cms.controller.admin.page')->public();
};
