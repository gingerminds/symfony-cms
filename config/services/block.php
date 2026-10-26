<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\CmsBundle\Block\Reference\FileReferenceResolver;
use Gingerminds\CmsBundle\Block\Reference\MediaReferenceResolver;
use Gingerminds\CmsBundle\Block\Type\Cards;
use Gingerminds\CmsBundle\Block\Type\FrequentlyAskedQuestions;
use Gingerminds\CmsBundle\Block\Type\LinkList;
use Gingerminds\CmsBundle\Block\Type\Media;
use Gingerminds\CmsBundle\Block\Type\MediaList;
use Gingerminds\CmsBundle\Block\Type\Slider;
use Gingerminds\CmsBundle\Block\Type\TextImage;
use Gingerminds\CmsBundle\Block\Type\TitleText;
use Gingerminds\CmsBundle\Block\Type\Video;
use Gingerminds\CmsBundle\Cache\PageReferenceCacheListener;
use Gingerminds\CmsBundle\Controller\Block\BlockController;
use Gingerminds\CmsBundle\DependencyInjection\Compiler\BlockPass;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Form\Block\BlockCanvasType;
use Gingerminds\CmsBundle\Form\Block\BlockDataType;
use Gingerminds\CmsBundle\Validator\ValidBlocksValidator;
use Gingerminds\MediaManagerBundle\File\Reference\FileReferenceSourceInterface;
use Gingerminds\MediaManagerBundle\File\Reference\JsonFieldFileReferenceSource;

/*
 * Content blocks: the blocks of the bundle, the registry (filled by BlockPass), the references
 * (API, previews, file usages, cache), the forms, the validation and the block modal controller.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $blocks = [
        TitleText::class,
        TextImage::class,
        Media::class,
        Video::class,
        Cards::class,
        Slider::class,
        LinkList::class,
        MediaList::class,
        FrequentlyAskedQuestions::class,
    ];

    foreach ($blocks as $block) {
        $services->set('gingerminds_cms.block.' . $block::getKey(), $block)
            ->tag(BlockInterface::TAG);
    }

    $services->set(BlockPass::REGISTRY, BlockRegistry::class)
        ->args([
            abstract_arg('blocks, set by BlockPass'),
            abstract_arg('block extensions, set by BlockPass'),
            abstract_arg('block extension ids, set by BlockPass'),
            param('gingerminds_cms.blocks.disabled'),
            param('gingerminds_cms.blocks.order'),
        ]);
    $services->alias(BlockRegistry::class, BlockPass::REGISTRY);

    $services->set('gingerminds_cms.block.content_normalizer', BlockContentNormalizer::class)
        ->args([service(BlockPass::REGISTRY), service('gingerminds_cms.wysiwyg.sanitizer')]);
    $services->alias(BlockContentNormalizer::class, 'gingerminds_cms.block.content_normalizer');

    $services->set('gingerminds_cms.block.reference.file', FileReferenceResolver::class)
        ->args([service('doctrine.orm.entity_manager'), service('gingerminds_media_manager.twig.extension'), param('gingerminds_media_manager.images.presets')])
        ->tag(BlockReferenceResolverInterface::TAG);

    $services->set('gingerminds_cms.block.reference.media', MediaReferenceResolver::class)
        ->args([service('doctrine.orm.entity_manager'), service('serializer')])
        ->tag(BlockReferenceResolverInterface::TAG);

    $services->set('gingerminds_cms.block.reference_resolver', ContentReferenceResolver::class)
        ->args([
            service(BlockPass::REGISTRY),
            tagged_locator(BlockReferenceResolverInterface::TAG, defaultIndexMethod: 'getFieldType'),
        ]);
    $services->alias(ContentReferenceResolver::class, 'gingerminds_cms.block.reference_resolver');

    // The files of the blocks are usages of the library: a used file is not deleted, a merge rewrites the content.
    $services->set('gingerminds_cms.block.file_reference_source', JsonFieldFileReferenceSource::class)
        ->args([service('doctrine.orm.entity_manager'), PageTranslation::class, 'content'])
        ->tag(FileReferenceSourceInterface::TAG);

    $services->set('gingerminds_cms.block.reference_cache_listener', PageReferenceCacheListener::class)
        ->args([
            service('gingerminds_core.cache.pool'),
            service('gingerminds_core.cache.key_builder'),
            param('gingerminds_cms.resource.page.entity'),
            param('gingerminds_core.cache.enabled'),
        ])
        ->tag('doctrine.event_listener', ['event' => 'onFlush'])
        ->tag('doctrine.event_listener', ['event' => 'postFlush'])
        ->tag('kernel.reset', ['method' => 'reset']);

    $services->set('gingerminds_cms.form.type.block_data', BlockDataType::class)
        ->args([service(BlockPass::REGISTRY)])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.block_canvas', BlockCanvasType::class)
        ->args([
            service(BlockPass::REGISTRY),
            service('gingerminds_cms.block.content_normalizer'),
            service('router'),
            service('gingerminds_cms.block.reference_resolver'),
        ])
        ->tag('form.type');

    $services->set('gingerminds_cms.validator.valid_blocks', ValidBlocksValidator::class)
        ->args([service(BlockPass::REGISTRY), service('translator')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.controller.admin.block', BlockController::class)
        ->args([
            service(BlockPass::REGISTRY),
            service('gingerminds_cms.block.content_normalizer'),
            service('gingerminds_cms.block.reference_resolver'),
            service('form.factory'),
            service('security.authorization_checker'),
            service('translator'),
            service('twig'),
        ])
        ->tag('controller.service_arguments')
        ->public();
    $services->alias(BlockController::class, 'gingerminds_cms.controller.admin.block')->public();
};
