<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use ApiPlatform\Doctrine\Orm\State\CollectionProvider;
use ApiPlatform\Doctrine\Orm\State\ItemProvider;
use Gingerminds\CmsBundle\ApiPlatform\Serializer\PageContentNormalizer;
use Gingerminds\CmsBundle\ApiPlatform\State\PageCollectionProvider;
use Gingerminds\CmsBundle\ApiPlatform\State\PageItemProvider;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

/*
 * API Platform: page providers (decorating the Doctrine ones) and normalizers.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.api.provider.page_collection', PageCollectionProvider::class)
        ->args([
            service(CollectionProvider::class),
            service('gingerminds_cms.page.filter_compute'),
            service('gingerminds_core.api.computed_filter_store'),
        ])
        ->tag('api_platform.state_provider');

    $services->set('gingerminds_cms.api.provider.page_item', PageItemProvider::class)
        ->args([
            service(ItemProvider::class),
            service(PageRepository::class),
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language'),
        ])
        ->tag('api_platform.state_provider');

    $services->set('gingerminds_cms.api.page_content_normalizer', PageContentNormalizer::class)
        ->args([service('gingerminds_cms.block.reference_resolver')])
        ->tag('serializer.normalizer', ['priority' => 64]);
};
