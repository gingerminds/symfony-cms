<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Form\Extension\SiteMainMenuTypeExtension;
use Gingerminds\CmsBundle\Form\Menu\MenuType;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;

/*
 * Form types and extensions.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.form.type.menu', MenuType::class)
        ->args([service('gingerminds_core.resource_registry')])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.extension.site_main_menu', SiteMainMenuTypeExtension::class)
        ->args([service(MenuRepository::class)])
        ->tag('form.type_extension');
};
