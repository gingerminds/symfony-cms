<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Form\Extension\SiteMainMenuTypeExtension;
use Gingerminds\CmsBundle\Form\Menu\MenuItemTranslationType;
use Gingerminds\CmsBundle\Form\Menu\MenuItemType;
use Gingerminds\CmsBundle\Form\Menu\MenuType;
use Gingerminds\CmsBundle\Form\Page\PageCategoryTranslationType;
use Gingerminds\CmsBundle\Form\Page\PageCategoryType;
use Gingerminds\CmsBundle\Form\Page\PageTranslationType;
use Gingerminds\CmsBundle\Form\Page\PageType;
use Gingerminds\CmsBundle\Form\Type\PublicationStatusType;
use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Gingerminds\CmsBundle\Repository\Menu\MenuRepository;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CmsBundle\Validator\LinkUrlValidator;
use Gingerminds\CmsBundle\Validator\SinglePageCategoryValidator;
use Gingerminds\CmsBundle\Validator\UniquePageUrlValidator;
use Gingerminds\CmsBundle\Validator\UniqueSiblingPrefixValidator;
use Gingerminds\CmsBundle\Validator\UniqueSlugInCategoryValidator;
use Gingerminds\CmsBundle\Wysiwyg\WysiwygSanitizer;

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

    $services->set('gingerminds_cms.form.type.page', PageType::class)
        ->args([
            service('gingerminds_core.resource_registry'),
            service(PageCategoryRepository::class),
            service('gingerminds_cms.page.preview_urls'),
            param('gingerminds_cms.slug_overwrite.create'),
            param('gingerminds_cms.slug_overwrite.edit'),
        ])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.page_translation', PageTranslationType::class)
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.publication_status', PublicationStatusType::class)
        ->args([service('workflow.registry')])
        ->tag('form.type');

    $services->set('gingerminds_cms.wysiwyg.sanitizer', WysiwygSanitizer::class);
    $services->alias(WysiwygSanitizer::class, 'gingerminds_cms.wysiwyg.sanitizer');

    $services->set('gingerminds_cms.form.type.wysiwyg', WysiwygType::class)
        ->args([param('gingerminds_cms.wysiwyg.presets'), service('gingerminds_cms.wysiwyg.sanitizer')])
        ->tag('form.type');

    $services->set('gingerminds_cms.validator.link_url', LinkUrlValidator::class)
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.validator.unique_slug_in_category', UniqueSlugInCategoryValidator::class)
        ->args([service('doctrine')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.validator.unique_page_url', UniquePageUrlValidator::class)
        ->args([service('doctrine')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.validator.single_page_category', SinglePageCategoryValidator::class)
        ->args([service(PageRepository::class)])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.validator.unique_sibling_prefix', UniqueSiblingPrefixValidator::class)
        ->args([service('doctrine')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.form.extension.site_main_menu', SiteMainMenuTypeExtension::class)
        ->args([service(MenuRepository::class)])
        ->tag('form.type_extension');
};
