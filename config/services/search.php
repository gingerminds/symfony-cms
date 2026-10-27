<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Command\ReindexSearchCommand;
use Gingerminds\CmsBundle\Doctrine\SearchIndexListener;
use Gingerminds\CmsBundle\Search\Api\SearchFilterComputeService;
use Gingerminds\CmsBundle\Search\Api\SearchProvider;
use Gingerminds\CmsBundle\Search\Doctrine\DoctrineSearchEngine;
use Gingerminds\CmsBundle\Search\Message\ReindexSearchCategoryHandler;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchIndexer;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;

/*
 * Public search: the types, the engine (`gingerminds_cms.search.engine`, aliased by the bundle),
 * the sync (listener, category message, command) and the API.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.search.resources', SearchResourceRegistry::class)
        ->args([param('gingerminds_cms.search.resources')]);
    $services->alias(SearchResourceRegistry::class, 'gingerminds_cms.search.resources');

    $services->set('gingerminds_cms.search.engine.doctrine', DoctrineSearchEngine::class)
        ->args([service('doctrine.dbal.default_connection')]);

    $services->set('gingerminds_cms.search.document_factory', SearchDocumentFactory::class)
        ->args([service('property_accessor')]);

    $services->set('gingerminds_cms.search.indexer', SearchIndexer::class)
        ->args([
            service('gingerminds_cms.search.resources'),
            service('gingerminds_cms.search.document_factory'),
            service(SearchEngineInterface::class),
            service('doctrine.orm.entity_manager'),
        ]);
    $services->alias(SearchIndexer::class, 'gingerminds_cms.search.indexer');

    $services->set('gingerminds_cms.search.index_listener', SearchIndexListener::class)
        ->args([service('gingerminds_cms.search.resources'), service('gingerminds_cms.search.indexer'), service('messenger.default_bus')])
        ->tag('doctrine.event_listener', ['event' => 'onFlush'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('gingerminds_cms.search.reindex_category_handler', ReindexSearchCategoryHandler::class)
        ->args([service('doctrine.orm.entity_manager'), service('gingerminds_cms.search.indexer')])
        ->tag('messenger.message_handler');

    $services->set('gingerminds_cms.command.search_reindex', ReindexSearchCommand::class)
        ->args([service('gingerminds_cms.search.resources'), service('gingerminds_cms.search.indexer')])
        ->tag('console.command');

    $services->set('gingerminds_cms.search.filter_compute', SearchFilterComputeService::class)
        ->args([service('gingerminds_cms.search.resources'), service('doctrine.orm.entity_manager'), service('translator')]);

    $services->set('gingerminds_cms.api.provider.search', SearchProvider::class)
        ->args([
            service(SearchEngineInterface::class),
            service('gingerminds_cms.search.resources'),
            service('gingerminds_cms.search.filter_compute'),
            service('gingerminds_core.api.computed_filter_store'),
            service('gingerminds_multisite.context.site'),
            service('gingerminds_multisite.context.language'),
            service('request_stack'),
            service('doctrine.orm.entity_manager'),
            service_closure('serializer'),
        ])
        ->tag('api_platform.state_provider');
};
