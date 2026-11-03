<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Gingerminds\CmsBundle\Controller\Menu\MenuController;
use Gingerminds\CmsBundle\Controller\Menu\MenuItemController;
use Gingerminds\CmsBundle\Controller\Page\PageCategoryController;
use Gingerminds\CmsBundle\Controller\Page\PageController;
use Gingerminds\CmsBundle\DependencyInjection\Compiler\BlockPass;
use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItem;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CmsBundle\Entity\Page\BasePage;
use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Form\Menu\MenuItemType;
use Gingerminds\CmsBundle\Form\Menu\MenuType;
use Gingerminds\CmsBundle\Form\Page\PageCategoryType;
use Gingerminds\CmsBundle\Form\Page\PageType;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\PublishableInterface;
use Gingerminds\CmsBundle\Search\PubliclySearchableInterface;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CoreBundle\Bundle\AbstractResourceBundle;
use Symfony\Bundle\MakerBundle\MakerBundle;
use Symfony\Component\Config\Definition\Configurator\DefinitionConfigurator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

/**
 * Menus, pages, content blocks and search on top of the core, media manager and multisite bundles.
 */
final class GingermindsCmsBundle extends AbstractResourceBundle
{
    /**
     * Overridable entities and admin resources of the bundle (AbstractResourceBundle::resources()).
     */
    public const array RESOURCES = [
        'menu' => [
            'entity' => Menu::class,
            'interface' => MenuInterface::class,
            'controller' => MenuController::class,
            'form' => MenuType::class,
            'path' => 'menus',
            'permission' => 'menus',
        ],
        'menu_item' => [
            'entity' => MenuItem::class,
            'interface' => MenuItemInterface::class,
            'controller' => MenuItemController::class,
            'form' => MenuItemType::class,
            'path' => 'menus/{menuId}/items',
            'permission' => 'menu_items',
        ],
        'page_category' => [
            'entity' => PageCategory::class,
            'interface' => PageCategoryInterface::class,
            'controller' => PageCategoryController::class,
            'form' => PageCategoryType::class,
            'path' => 'page-categories',
            'permission' => 'page_categories',
        ],
        'page' => [
            'entity' => Page::class,
            'interface' => PageInterface::class,
            'controller' => PageController::class,
            'form' => PageType::class,
            'path' => 'pages',
            'permission' => 'pages',
        ],
    ];

    public const string TRANSLATION_DOMAIN = 'GingermindsCms';

    public const string SEARCH_ENGINE_DOCTRINE = 'doctrine';

    protected string $extensionAlias = 'gingerminds_cms';

    public function getPath(): string
    {
        return \dirname(__DIR__);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new BlockPass());
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

        $builder->registerForAutoconfiguration(BlockInterface::class)->addTag(BlockInterface::TAG);
        $builder->registerForAutoconfiguration(BlockExtensionInterface::class)->addTag(BlockExtensionInterface::TAG);
        $builder->registerForAutoconfiguration(BlockReferenceResolverInterface::class)->addTag(BlockReferenceResolverInterface::TAG);

        if (class_exists(MakerBundle::class)) {
            $container->import('../config/services/maker.php');
        }

        $parameters = $container->parameters();
        $parameters->set('gingerminds_cms.wysiwyg.presets', $config['wysiwyg']['presets']);
        $parameters->set('gingerminds_cms.slug_overwrite.create', $config['slug_overwrite']['create']);
        $parameters->set('gingerminds_cms.slug_overwrite.edit', $config['slug_overwrite']['edit']);
        $parameters->set('gingerminds_cms.blocks.disabled', $config['blocks']['disabled']);
        $parameters->set('gingerminds_cms.blocks.order', $config['blocks']['order']);
        $parameters->set('gingerminds_cms.publication.status_colors', $config['publication']['status_colors']);
        $parameters->set('gingerminds_cms.search.resources', $this->searchResources($config));
        $engine = $config['search']['engine'];
        $builder->setAlias(SearchEngineInterface::class, self::SEARCH_ENGINE_DOCTRINE === $engine ? 'gingerminds_cms.search.engine.doctrine' : $engine);
        $builder->setAlias('gingerminds_cms.search.engine', SearchEngineInterface::class);

        parent::loadExtension($config, $container, $builder);
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        parent::prependExtension($container, $builder);

        $config = $this->resolveConfig($builder);
        $mappings = [];

        // The tables of the `doctrine` search engine, only when it is the engine.
        if (self::SEARCH_ENGINE_DOCTRINE === ($config['search']['engine'] ?? self::SEARCH_ENGINE_DOCTRINE)) {
            $mappings['GingermindsCmsSearch'] = $this->mapping('Search/Doctrine/Entity');
        }

        $builder->prependExtensionConfig('api_platform', ['mapping' => ['paths' => [$this->getPath() . '/src/Search/Api']]]);
        $builder->prependExtensionConfig($this->extensionAlias, ['search' => ['resources' => ['page' => [
            'label' => 'page.name_p',
            'category' => 'category',
            'group' => BasePage::GROUP_LIST,
            'fields' => ['title' => ['weight' => 3], 'hook' => ['weight' => 1]],
        ]]]]);

        $siteEntity = $builder->getParameterBag()->resolveValue(self::extensionConfigValue($builder, 'gingerminds_multisite', 'resources.site.entity'));

        if (\is_string($siteEntity) && Site::class === ltrim($siteEntity, '\\')) {
            $mappings['GingermindsCmsSite'] = $this->mapping('Site/Entity');
        }

        if ([] !== $mappings) {
            $builder->prependExtensionConfig('doctrine', ['orm' => ['mappings' => $mappings]]);
        }

        // Prepended: the project configuration still overrides any key.
        $builder->prependExtensionConfig('gingerminds_core', [
            'admin_includes' => [
                'head' => ['@GingermindsCms/admin/_head.html.twig'],
                'site_form_sidebar' => ['@GingermindsCms/site/_main_menu.html.twig'],
            ],
        ]);

        if ($builder->hasExtension('twig')) {
            $builder->prependExtensionConfig('twig', [
                'form_themes' => [
                    '@GingermindsCms/form/translations_theme.html.twig',
                    '@GingermindsCms/form/wysiwyg_theme.html.twig',
                    '@GingermindsCms/form/blocks_theme.html.twig',
                ],
            ]);
        }

        $builder->prependExtensionConfig('framework', [
            'asset_mapper' => [
                'paths' => [$this->getPath() . '/assets' => 'gingerminds-cms'],
            ],
            // Completed or redefined by the project (its own statuses).
            'workflows' => [
                PublicationStatus::WORKFLOW => [
                    'type' => 'state_machine',
                    'marking_store' => ['type' => 'method', 'property' => 'status'],
                    'supports' => [PublishableInterface::class],
                    'initial_marking' => PublicationStatus::DRAFT,
                    'places' => PublicationStatus::PLACES,
                    'transitions' => array_map(
                        static fn (array $places): array => ['from' => $places[0], 'to' => $places[1]],
                        PublicationStatus::TRANSITIONS,
                    ),
                ],
            ],
        ]);
    }

    /**
     * The search types with their entity (null: the entity of the CMS resource of the same name).
     *
     * @param array<string, mixed> $config
     *
     * @return array<string, array<string, mixed>>
     */
    private function searchResources(array $config): array
    {
        $resources = [];

        foreach ($config['search']['resources'] as $type => $resource) {
            $entity = $resource['entity'] ?? (isset(self::RESOURCES[$type]) ? $this->resourceValue($config, $type, 'entity', self::RESOURCES[$type]) : null);

            if (!\is_string($entity) || !is_subclass_of($entity, PubliclySearchableInterface::class)) {
                throw new LogicException(\sprintf('The search type "%s" needs an "entity" implementing "%s".', $type, PubliclySearchableInterface::class));
            }

            $resources[$type] = ['entity' => $entity, ...$resource];
            $resources[$type]['entity'] = $entity;
        }

        return $resources;
    }

    protected function resources(array $config): array
    {
        return self::RESOURCES;
    }

    protected function entityDirectories(): array
    {
        return ['Menu', 'Page'];
    }

    protected function prependConfigKeys(): array
    {
        return ['resources', 'search'];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapping(string $directory): array
    {
        return [
            'type' => 'attribute',
            'is_bundle' => false,
            'dir' => $this->getPath() . '/src/' . $directory,
            'prefix' => 'Gingerminds\\CmsBundle\\' . str_replace('/', '\\', $directory),
        ];
    }
}
