<?php

declare(strict_types=1);

use Symfony\Component\Config\Definition\Builder\NodeBuilder;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;

return static function (DefinitionConfigurator $definition): void {
    $wysiwyg = static function (NodeBuilder $children): void {
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
                                    ->info('Toolbar entries, in display order: bold, italic, underline, strike, link, bulletList, orderedList,'
                                        . ' blockquote, heading, horizontalRule, table, history, or a name added by a gm-cms-wysiwyg:pre-connect listener.')
                                    ->isRequired()
                                    ->requiresAtLeastOneElement()
                                    ->scalarPrototype()->cannotBeEmpty()->end()
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

    $resource = static function (NodeBuilder $children, string $name, string $baseClass): void {
        $children
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('entity')
                        ->info('Entity class, extending the bundle Base' . $baseClass . ' (null: the bundle one).')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('controller')->defaultNull()->end()
                    ->scalarNode('form')->defaultNull()->end()
                    ->booleanNode('api')
                        ->info('false stops exposing the entity through the API (the bundle #[ApiResource], inherited by an entity overriding it).')
                        ->defaultTrue()
                    ->end()
                ->end()
            ->end();
    };

    $entity = static function (NodeBuilder $children, string $name, string $baseClass): void {
        $children
            ->arrayNode($name)
                ->addDefaultsIfNotSet()
                ->children()
                    ->scalarNode('entity')
                        ->info('Entity class, extending the bundle Base' . $baseClass . ' (null: the bundle one).')
                        ->defaultNull()
                    ->end()
                ->end()
            ->end();
    };

    $children = $definition->rootNode()->children();

    $resources = $children->arrayNode('resources')
        ->info('Overridable resources of the bundle, registered as gingerminds_core admin resources.')
        ->addDefaultsIfNotSet()
        ->children();
    $resource($resources, 'menu', 'Menu');
    $resource($resources, 'menu_item', 'MenuItem');
    $resource($resources, 'page_category', 'PageCategory');
    $resource($resources, 'page', 'Page');
    $entity($resources, 'menu_item_translation', 'MenuItemTranslation');
    $entity($resources, 'page_category_translation', 'PageCategoryTranslation');
    $entity($resources, 'page_translation', 'PageTranslation');
    $resources->end()->end();

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
        ->end()
        ->arrayNode('search')
            ->info('Public search (/api/search-indices, /api/search-results); the indexed types: #[SearchIndex] entities.')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('engine')
                    ->info('"doctrine" (the search_index tables of the bundle) or the id of a service implementing SearchEngineInterface.')
                    ->defaultValue('doctrine')
                    ->cannotBeEmpty()
                ->end()
                ->integerNode('message_threshold')
                    ->info('Contents changed by a flush above which they are reindexed by ReindexSearchContents messages of this size'
                        . ' (route them to an async transport), instead of during the request.')
                    ->defaultValue(100)
                    ->min(1)
                ->end()
            ->end()
        ->end()
        ->arrayNode('publication')
            ->info('Publication workflow of the pages and of the other PublishableInterface resources.')
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('status_colors')
                    ->info('Bootstrap color of the status badges by workflow place (secondary for the others).')
                    ->useAttributeAsKey('status')
                    ->normalizeKeys(false)
                    ->scalarPrototype()->cannotBeEmpty()->end()
                    ->defaultValue(['draft' => 'primary', 'published' => 'success', 'archived' => 'warning'])
                ->end()
            ->end()
        ->end();
};
