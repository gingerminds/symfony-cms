<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle;

use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

/**
 * Menus, pages, content blocks and search on top of the core, media manager and multisite bundles.
 */
final class GingermindsCmsBundle extends AbstractBundle
{
    public const string TRANSLATION_DOMAIN = 'GingermindsCms';

    protected string $extensionAlias = 'gingerminds_cms';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function configure(DefinitionConfigurator $definition): void
    {
        $definition->import('../config/definition.php');
    }

    /**
     * @param array<string, mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.php');

        $parameters = $container->parameters();
        $parameters->set('gingerminds_cms.wysiwyg.presets', $config['wysiwyg']['presets']);
        $parameters->set('gingerminds_cms.slug_overwrite.create', $config['slug_overwrite']['create']);
        $parameters->set('gingerminds_cms.slug_overwrite.edit', $config['slug_overwrite']['edit']);
        $parameters->set('gingerminds_cms.blocks.disabled', $config['blocks']['disabled']);
        $parameters->set('gingerminds_cms.blocks.order', $config['blocks']['order']);
    }
}
