<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;

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
};
