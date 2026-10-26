<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Doctrine\ORM\Events;
use Gingerminds\CmsBundle\Command\SyncPageUrlsCommand;
use Gingerminds\CmsBundle\Doctrine\PageUrlListener;
use Gingerminds\CmsBundle\File\PageTranslationUsageResolver;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Page\PageFilterComputeService;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Page\PreviewUrls;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CmsBundle\Twig\PublicationExtension;
use Gingerminds\CmsBundle\Workflow\PublicationDatesListener;
use Gingerminds\MediaManagerBundle\File\Reference\FileUsageResolverInterface;

/*
 * Pages and publication: workflow dates, status badges, URL paths, API facets, preview, file usages.
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

    $services->set('gingerminds_cms.page.url_syncer', PageUrlSyncer::class)
        ->args([service('doctrine.orm.entity_manager')])
        ->tag('kernel.reset', ['method' => 'reset']);
    $services->alias(PageUrlSyncer::class, 'gingerminds_cms.page.url_syncer');

    $services->set('gingerminds_cms.doctrine.page_url_listener', PageUrlListener::class)
        ->args([service('gingerminds_cms.page.url_syncer'), service(PageRepository::class), service(PageCategoryRepository::class)])
        ->tag('doctrine.event_listener', ['event' => Events::onFlush])
        ->tag('doctrine.event_listener', ['event' => Events::postFlush])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('gingerminds_cms.page.filter_compute', PageFilterComputeService::class)
        ->args([service(PageRepository::class), param('gingerminds_cms.resource.page.entity')])
        ->call('setEntityManager', [service('doctrine.orm.entity_manager')]);

    $services->set('gingerminds_cms.command.sync_page_urls', SyncPageUrlsCommand::class)
        ->args([service(PageRepository::class), service('gingerminds_cms.page.url_syncer'), service('doctrine.orm.entity_manager')])
        ->tag('console.command');
};
