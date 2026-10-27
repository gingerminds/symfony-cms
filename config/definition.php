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
                ->end()
            ->end();
    };

    $children = $definition->rootNode()->children();

    $resources = $children->arrayNode('resources')
        ->info('Overridable resources of the bundle, registered as gingerminds_core resources.')
        ->addDefaultsIfNotSet()
        ->children();
    $resource($resources, 'menu', 'Menu');
    $resource($resources, 'menu_item', 'MenuItem');
    $resource($resources, 'page_category', 'PageCategory');
    $resource($resources, 'page', 'Page');
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
            ->info('Public search (/api/search-indices, /api/search-results).')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('engine')
                    ->info('"doctrine" (the search_index tables of the bundle) or the id of a service implementing SearchEngineInterface.')
                    ->defaultValue('doctrine')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('resources')
                    ->info('Searchable contents by type (the bundle declares "page"); a project adds its own.')
                    ->useAttributeAsKey('type')
                    ->normalizeKeys(false)
                    ->arrayPrototype()
                        ->children()
                            ->scalarNode('entity')
                                ->info('Entity implementing PubliclySearchableInterface (null: the entity of the CMS resource of this name).')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('label')->info('Label of the type facet (translation key, null: the type).')->defaultNull()->end()
                            ->scalarNode('translation_domain')->defaultValue('GingermindsCms')->end()
                            ->scalarNode('category')
                                ->info('Association to the categories: category facets, reindex of the contents of a category hidden from search.')
                                ->defaultNull()
                            ->end()
                            ->scalarNode('group')->info('Serialization group of the content in /api/search-results (null: no content).')->defaultNull()->end()
                            ->arrayNode('fields')
                                ->info('Searchable fields: property path on the entry subject (its translation), else on the entity; weight 0 removes a field.')
                                ->useAttributeAsKey('name')
                                ->normalizeKeys(false)
                                ->arrayPrototype()
                                    ->treatNullLike([])
                                    ->children()
                                        ->integerNode('weight')->min(0)->defaultValue(1)->end()
                                    ->end()
                                ->end()
                            ->end()
                        ->end()
                    ->end()
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
