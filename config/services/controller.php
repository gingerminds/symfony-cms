<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Controller\Menu\MenuController;

/*
 * Admin controllers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.controller.admin.menu', MenuController::class)
        ->args([service('gingerminds_core.controller.context'), service('gingerminds_multisite.context.site')])
        ->tag('controller.service_arguments');
    $services->alias(MenuController::class, 'gingerminds_cms.controller.admin.menu')->public();
};
