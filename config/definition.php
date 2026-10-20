<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $extensions = ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'blockquote', 'heading', 'horizontalRule', 'table', 'history'];

    $wysiwyg = static function (NodeBuilder $children) use ($extensions): void {
        $children
            ->arrayNode('wysiwyg')
                ->info('WYSIWYG (TipTap) editor.')
                ->addDefaultsIfNotSet()
                ->children()
                    ->arrayNode('presets')
                        ->info('Toolbar presets, picked by name on a field. Custom presets replace the defaults.')
                        ->useAttributeAsKey('name')
                        ->arrayPrototype()
                            ->children()
                                ->arrayNode('extensions')
                                    ->info('TipTap extensions of the toolbar, in display order.')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->enumPrototype()->values($extensions)->end()
                                ->end()
                            ->end()
                        ->end()
                        ->defaultValue([
                            'default' => ['extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'history']],
                            'extended' => ['extensions' => ['bold', 'italic', 'underline', 'strike', 'link', 'bulletList', 'orderedList', 'table', 'history']],
                            'minimal' => ['extensions' => ['bold', 'italic', 'underline', 'link']],
                            'full' => ['extensions' => [
                                'bold', 'italic', 'strike', 'underline', 'link', 'bulletList', 'orderedList', 'blockquote', 'heading', 'horizontalRule', 'history',
                            ]],
                        ])
                        ->validate()
                            ->ifTrue(static fn (array $presets): bool => !isset($presets['default']))
                            ->thenInvalid('The "default" WYSIWYG preset is required.')
                        ->end()
                    ->end()
                ->end()
            ->end();
    };

    $children = $definition->rootNode()->children();

    $wysiwyg($children);

    $children
        ->arrayNode('slug_overwrite')
            ->info('Whether typing a title keeps overwriting the slug, on the create and on the edit forms.')
            ->addDefaultsIfNotSet()
            ->children()
                ->booleanNode('create')->defaultTrue()->end()
                ->booleanNode('edit')->defaultFalse()->end()
            ->end()
        ->end()
        ->arrayNode('blocks')
            ->info('Content blocks of the pages.')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('disabled')
                    ->info('Keys of the blocks hidden from the block picker.')
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('order')
                    ->info('Block key => picker weight, overriding the block own order (ascending).')
                    ->useAttributeAsKey('key')
                    ->normalizeKeys(false)
                    ->integerPrototype()->end()
                ->end()
            ->end()
        ->end();
};
