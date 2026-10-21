<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Form\Extension\SiteMainMenuTypeExtension;
use Gingerminds\CmsBundle\Form\Menu\MenuItemTranslationType;
use Gingerminds\CmsBundle\Form\Menu\MenuItemType;
use Gingerminds\CmsBundle\Form\Menu\MenuType;
use Gingerminds\CmsBundle\Form\Page\PageCategoryTranslationType;
use Gingerminds\CmsBundle\Form\Page\PageCategoryType;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Validator\UniqueSiblingPrefixValidator;

/*
 * Form types, extensions and validators.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.form.type.menu', MenuType::class)
        ->args([service('gingerminds_core.resource_registry')])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.menu_item', MenuItemType::class)
        ->args([service('gingerminds_core.resource_registry'), service(MenuItemRepository::class)])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.menu_item_translation', MenuItemTranslationType::class)
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.page_category', PageCategoryType::class)
        ->args([
            service('gingerminds_core.resource_registry'),
            service(PageCategoryRepository::class),
            param('gingerminds_cms.slug_overwrite.create'),
            param('gingerminds_cms.slug_overwrite.edit'),
        ])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.page_category_translation', PageCategoryTranslationType::class)
        ->tag('form.type');

    $services->set('gingerminds_cms.validator.unique_sibling_prefix', UniqueSiblingPrefixValidator::class)
        ->args([service('doctrine')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.form.extension.site_main_menu', SiteMainMenuTypeExtension::class)
        ->args([service(MenuRepository::class)])
        ->tag('form.type_extension');
};
