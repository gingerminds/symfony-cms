<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\CmsBundle\Block\Reference\FileReferenceResolver;
use Gingerminds\CmsBundle\Block\Reference\MediaReferenceResolver;
use Gingerminds\CmsBundle\Block\Type\CardsType;
use Gingerminds\CmsBundle\Block\Type\CardType;
use Gingerminds\CmsBundle\Block\Type\FaqItemType;
use Gingerminds\CmsBundle\Block\Type\FaqType;
use Gingerminds\CmsBundle\Block\Type\LinkListType;
use Gingerminds\CmsBundle\Block\Type\LinkType;
use Gingerminds\CmsBundle\Block\Type\MediaListItemType;
use Gingerminds\CmsBundle\Block\Type\MediaListType;
use Gingerminds\CmsBundle\Block\Type\MediaType;
use Gingerminds\CmsBundle\Block\Type\SliderType;
use Gingerminds\CmsBundle\Block\Type\SlideType;
use Gingerminds\CmsBundle\Block\Type\TextImageType;
use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Gingerminds\CmsBundle\Block\Type\VideoType;
use Gingerminds\CmsBundle\Cache\PageReferenceCacheListener;
use Gingerminds\CmsBundle\Controller\Block\BlockController;
use Gingerminds\CmsBundle\Form\Block\BlockCanvasType;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Gingerminds\CmsBundle\Twig\BlockExtension;
use Gingerminds\CmsBundle\Validator\ValidBlocksValidator;
use Gingerminds\MediaManagerBundle\File\Reference\FileReferenceSourceInterface;
use Gingerminds\MediaManagerBundle\File\Reference\JsonFieldFileReferenceSource;

/*
 * Content blocks: the blocks of the bundle, the registry (#[AsContentBlock] form types), the references
 * (API, previews, file usages, cache), the canvas form, the validation and the block modal controller.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    // #[AsContentBlock] form types (autoconfigured): replace a service to replace a block.
    $blocks = [
        'title_text' => TitleTextType::class,
        'text_image' => TextImageType::class,
        'media' => MediaType::class,
        'video' => VideoType::class,
        'cards' => CardsType::class,
        'slider' => SliderType::class,
        'link_list' => LinkListType::class,
        'media_list' => MediaListType::class,
        'faq' => FaqType::class,
    ];

    foreach ($blocks as $key => $block) {
        $services->set('gingerminds_cms.block.' . $key, $block)->autoconfigure();
    }

    foreach ([CardType::class, SlideType::class, LinkType::class, MediaListItemType::class, FaqItemType::class, BlockCollectionType::class] as $type) {
        $services->set($type)->tag('form.type');
    }

    $services->set('gingerminds_cms.block.registry', BlockRegistry::class)
        ->args([
            tagged_locator(BlockRegistry::TAG),
            service('form.factory'),
            param('gingerminds_cms.blocks.disabled'),
            param('gingerminds_cms.blocks.order'),
        ]);
    $services->alias(BlockRegistry::class, 'gingerminds_cms.block.registry');

    $services->set('gingerminds_cms.block.content_normalizer', BlockContentNormalizer::class)
        ->args([service('gingerminds_cms.block.registry')]);
    $services->alias(BlockContentNormalizer::class, 'gingerminds_cms.block.content_normalizer');

    // Autoconfigured: tagged, and indexed by their #[AsTaggedItem] form type.
    $services->set('gingerminds_cms.block.reference.file', FileReferenceResolver::class)
        ->args([service('doctrine.orm.entity_manager'), service('gingerminds_media_manager.twig.extension'), param('gingerminds_media_manager.images.presets')])
        ->autoconfigure();

    $services->set('gingerminds_cms.block.reference.media', MediaReferenceResolver::class)
        ->args([service('doctrine.orm.entity_manager'), service('serializer')])
        ->autoconfigure();

    $services->set('gingerminds_cms.block.reference_resolver', ContentReferenceResolver::class)
        ->args([
            service('gingerminds_cms.block.registry'),
            tagged_locator(BlockReferenceResolverInterface::TAG),
            service('cache.system'),
        ]);
    $services->alias(ContentReferenceResolver::class, 'gingerminds_cms.block.reference_resolver');

    // The files of the blocks are usages of the library: a used file is not deleted, a merge rewrites the content.
    $services->set('gingerminds_cms.block.file_reference_source', JsonFieldFileReferenceSource::class)
        ->args([service('doctrine.orm.entity_manager'), param('gingerminds_cms.resource.page_translation.entity'), 'content'])
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

    $services->set('gingerminds_cms.form.type.block_canvas', BlockCanvasType::class)
        ->args([
            service('gingerminds_cms.block.registry'),
            service('gingerminds_cms.block.content_normalizer'),
            service('router'),
            service('gingerminds_cms.block.reference_resolver'),
        ])
        ->tag('form.type');

    $services->set('gingerminds_cms.validator.valid_blocks', ValidBlocksValidator::class)
        ->args([service('gingerminds_cms.block.registry'), service('translator')])
        ->tag('validator.constraint_validator');

    // #[AsTwigFunction] methods (autoconfigured).
    $services->set('gingerminds_cms.twig.block_extension', BlockExtension::class)->autoconfigure();

    $services->set('gingerminds_cms.controller.admin.block', BlockController::class)
        ->args([
            service('gingerminds_cms.block.registry'),
            service('gingerminds_cms.block.content_normalizer'),
            service('gingerminds_cms.block.reference_resolver'),
            service('security.authorization_checker'),
            service('translator'),
            service('twig'),
        ])
        ->tag('controller.service_arguments')
        ->public();
    $services->alias(BlockController::class, 'gingerminds_cms.controller.admin.block')->public();
};
