<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Menu\CmsAdminMenuProvider;

/*
 * Admin menu.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.admin_menu.provider', CmsAdminMenuProvider::class)
        ->args([service('gingerminds_core.resource_registry')])
        ->tag('gingerminds_core.admin_menu_provider');
};
