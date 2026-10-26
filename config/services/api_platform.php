<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\ApiPlatform\State\PageProvider;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CoreBundle\ApiPlatform\State\ResourceProvider;

/*
 * API Platform state providers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.api.provider.menu', ResourceProvider::class)
        ->args([service(MenuRepository::class), service('request_stack')])
        ->tag('api_platform.state_provider');

    $services->set('gingerminds_cms.api.provider.page', PageProvider::class)
        ->args([
            service(PageRepository::class),
            service('request_stack'),
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language'),
            service('gingerminds_cms.page.filter_compute'),
            service('gingerminds_core.api.computed_filter_store'),
        ])
        ->tag('api_platform.state_provider');
};
