<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Controller\Menu\MenuController;
use Gingerminds\CmsBundle\Controller\Menu\MenuItemController;
use Gingerminds\CmsBundle\Controller\Page\PageCategoryController;
use Gingerminds\CmsBundle\Controller\Page\PageController;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Psr\Container\ContainerInterface;

/*
 * Admin controllers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // AbstractController: services read from their locator.
    $admin = static fn (string $id, string $class): ServiceConfigurator => $services->set($id, $class)
        ->public()
        ->tag('controller.service_arguments')
        ->tag('container.service_subscriber')
        ->call('setContainer', [service(ContainerInterface::class)]);

    $admin('gingerminds_cms.controller.admin.menu', MenuController::class)
        ->args([service('gingerminds_multisite.context.site')]);
    $services->alias(MenuController::class, 'gingerminds_cms.controller.admin.menu')->public();

    $admin('gingerminds_cms.controller.admin.menu_item', MenuItemController::class);
    $services->alias(MenuItemController::class, 'gingerminds_cms.controller.admin.menu_item')->public();

    $admin('gingerminds_cms.controller.admin.page_category', PageCategoryController::class)
        ->args([service('gingerminds_multisite.context.site'), service('gingerminds_cms.page.url_syncer')]);
    $services->alias(PageCategoryController::class, 'gingerminds_cms.controller.admin.page_category')->public();

    $admin('gingerminds_cms.controller.admin.page', PageController::class)
        ->args([
            service('gingerminds_multisite.context.site'),
            service(PageCategoryRepository::class),
            service('state_machine.gingerminds_cms_publication'),
        ]);
    $services->alias(PageController::class, 'gingerminds_cms.controller.admin.page')->public();
};
