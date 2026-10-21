<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\CoreBundle\ApiPlatform\State\ResourceProvider;

/*
 * API Platform state providers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.api.provider.menu', ResourceProvider::class)
        ->args([service(MenuRepository::class), service('request_stack')])
        ->tag('api_platform.state_provider');
};
