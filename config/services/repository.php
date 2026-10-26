<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

/*
 * Repositories. Doctrine requires the FQCN as service id: `gingerminds_cms.repository.*` are aliases.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(MenuRepository::class)
        ->args([service('doctrine'), param('gingerminds_cms.resource.menu.entity')])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_cms.repository.menu', MenuRepository::class);

    $services->set(MenuItemRepository::class)
        ->args([service('doctrine'), param('gingerminds_cms.resource.menu_item.entity')])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_cms.repository.menu_item', MenuItemRepository::class);

    $services->set(PageCategoryRepository::class)
        ->args([service('doctrine'), param('gingerminds_cms.resource.page_category.entity')])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_cms.repository.page_category', PageCategoryRepository::class);

    $services->set(PageRepository::class)
        ->args([service('doctrine'), param('gingerminds_cms.resource.page.entity')])
        ->call('setFilterHandlerRegistry', [service('gingerminds_core.filter_handler_registry')])
        ->call('setDisplayTimezone', [service('gingerminds_core.display_timezone')])
        ->tag('doctrine.repository_service');
    $services->alias('gingerminds_cms.repository.page', PageRepository::class);
};
