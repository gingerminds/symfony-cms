<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\File\PageTranslationUsageResolver;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Page\PreviewUrls;
use Gingerminds\CmsBundle\Twig\PublicationExtension;
use Gingerminds\CmsBundle\Workflow\PublicationDatesListener;
use Gingerminds\MediaManagerBundle\File\Reference\FileUsageResolverInterface;

/*
 * Pages and publication: workflow dates, status badges, preview, file usages.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.workflow.publication_dates_listener', PublicationDatesListener::class)
        ->tag('kernel.event_listener', ['event' => 'workflow.' . PublicationStatus::WORKFLOW . '.entered']);

    $services->set('gingerminds_cms.twig.publication_extension', PublicationExtension::class)
        ->args([param('gingerminds_cms.publication.status_colors')])
        ->tag('twig.extension');

    $services->set('gingerminds_cms.page.preview_urls', PreviewUrls::class);
    $services->alias(PreviewUrls::class, 'gingerminds_cms.page.preview_urls');

    $services->set('gingerminds_cms.file.page_translation_usage_resolver', PageTranslationUsageResolver::class)
        ->args([service('gingerminds_core.resource_registry'), service('router'), service('translator')])
        ->tag(FileUsageResolverInterface::TAG);
};
