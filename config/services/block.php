<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Type\TitleText;
use Gingerminds\CmsBundle\Controller\Block\BlockController;
use Gingerminds\CmsBundle\DependencyInjection\Compiler\BlockPass;
use Gingerminds\CmsBundle\Form\Block\BlockCanvasType;
use Gingerminds\CmsBundle\Form\Block\BlockDataType;
use Gingerminds\CmsBundle\Validator\ValidBlocksValidator;

/*
 * Content blocks: the blocks of the bundle, the registry (filled by BlockPass), the forms, the
 * validation and the block modal controller.
 */
return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set('gingerminds_cms.block.title_text', TitleText::class)
        ->tag(BlockInterface::TAG);

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

    $services->set('gingerminds_cms.form.type.block_data', BlockDataType::class)
        ->args([service(BlockPass::REGISTRY)])
        ->tag('form.type');

    $services->set('gingerminds_cms.form.type.block_canvas', BlockCanvasType::class)
        ->args([service(BlockPass::REGISTRY), service('gingerminds_cms.block.content_normalizer'), service('router')])
        ->tag('form.type');

    $services->set('gingerminds_cms.validator.valid_blocks', ValidBlocksValidator::class)
        ->args([service(BlockPass::REGISTRY), service('translator')])
        ->tag('validator.constraint_validator');

    $services->set('gingerminds_cms.controller.admin.block', BlockController::class)
        ->args([
            service(BlockPass::REGISTRY),
            service('gingerminds_cms.block.content_normalizer'),
            service('form.factory'),
            service('security.authorization_checker'),
            service('translator'),
            service('twig'),
        ])
        ->tag('controller.service_arguments')
        ->public();
    $services->alias(BlockController::class, 'gingerminds_cms.controller.admin.block')->public();
};
