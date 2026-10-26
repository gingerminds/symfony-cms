<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Gingerminds\CmsBundle\DependencyInjection\Compiler\BlockPass;
use Gingerminds\CmsBundle\Maker\MakeBlock;

/*
 * make:gm:cms-block, loaded by GingermindsCmsBundle::loadExtension() only when symfony/maker-bundle is installed.
 */
return static function (ContainerConfigurator $container): void {
    $container->services()
        ->set('gingerminds_cms.maker.block', MakeBlock::class)
        ->args([service(BlockPass::REGISTRY)])
        ->tag('maker.command');
};
