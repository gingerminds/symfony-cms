# Installation

Install and configure [`gingerminds/symfony-core`](https://github.com/gingerminds/symfony-core) (^1.9),
[`gingerminds/symfony-media-manager`](https://github.com/gingerminds/symfony-media-manager) (^1.2)
and [`gingerminds/symfony-multisite`](https://github.com/gingerminds/symfony-multisite) (^1.2)
first: the CMS registers its resources, admin menu, permissions and assets into the core, picks
its files and medias in the media manager and scopes its contents by site and language.

## 1. Require the bundle

```bash
composer require gingerminds/symfony-cms
```

Register it after the core, media manager and multisite bundles (Flex does it for you) in
`config/bundles.php`:

```php
return [
    // ...
    Gingerminds\CoreBundle\GingermindsCoreBundle::class => ['all' => true],
    Gingerminds\MediaManagerBundle\GingermindsMediaManagerBundle::class => ['all' => true],
    Gingerminds\MultisiteBundle\GingermindsMultisiteBundle::class => ['all' => true],
    Gingerminds\CmsBundle\GingermindsCmsBundle::class => ['all' => true],
];
```

## 2. Routes

```yaml
# config/routes/gingerminds_cms.yaml — reordering of the trees, block modal of the pages
gingerminds_cms:
    resource: '@GingermindsCmsBundle/config/routes.php'
```

The CRUD routes (`/admin/menus`, `/admin/menus/{menuId}/items`, `/admin/page-categories`,
`/admin/pages`) come from the core `gingerminds_crud` loader, the API routes (`/api/menus`,
`/api/pages`, `/api/search-indices`, `/api/search-results`) from API Platform: nothing to add.

## 3. The site entity

The CMS adds a main menu to the sites (`sites.main_menu_id`). Use its site entity:

```yaml
# config/packages/gingerminds_multisite.yaml
gingerminds_multisite:
    resources:
        site:
            entity: Gingerminds\CmsBundle\Site\Entity\Site
```

A project with its own site entity adds `SiteMainMenuInterface` and `SiteMainMenuTrait`
(`Gingerminds\CmsBundle\Model`) to it instead. See [Menus](Menus.md#main-menu-of-a-site).

## 4. Assets

The admin assets (Stimulus controllers, styles) are mapped by the bundle and loaded through the
core `head` include: nothing to import. The WYSIWYG editor needs TipTap 3 in the project importmap:

```bash
bin/console importmap:require @tiptap/core @tiptap/starter-kit @tiptap/extension-table
```

If jsDelivr cannot resolve the version of `orderedmap` (a ProseMirror dependency), require it
explicitly: `bin/console importmap:require orderedmap@2.1.1`. `sortablejs` and `bootstrap` come
with the core.

Compiled assets (`asset-map:compile`) take precedence over the importmap, in dev too: delete
`public/assets/` after adding packages if it exists.

## 5. Database

The bundle ships no migration: generate it from the mapping.

```bash
bin/console doctrine:migrations:diff
bin/console doctrine:migrations:migrate
```

It creates the tables of `gingerminds/laravel-cms` (`menus`, `menu_items`, `menu_item_translations`,
`page_categories`, `page_category_translations`, `pages`, `page_translations`, `page_urls`), the
`sites.main_menu_id` column and, with the default `doctrine` search engine, `search_index` and
`search_index_categories`. Coming from a Laravel database, see [Coming from laravel-cms](FromLaravel.md#importing-a-laravel-database).

## 6. Permissions

```bash
bin/console gingerminds:permissions:sync
```

Creates `view|edit|delete` permissions for `menus`, `menu_items`, `page_categories` and `pages`.
Give them to the editors' roles in the admin. The APIs are public.

## 7. Messenger

The search reindexes the pages of a category hidden from search (or shown again) through a
Messenger message, handled synchronously by default. To keep the admin save fast on big
categories, route it to an async transport:

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        routing:
            Gingerminds\CmsBundle\Search\Message\ReindexSearchCategory: async
```

## 8. After the installation

```bash
bin/console gingerminds:cms:page-urls:sync   # URL paths of the existing pages
bin/console gingerminds:cms:search:reindex   # search index of the existing contents
```

Both are needed once on an imported database; afterwards the URLs and the index follow every
change. See [Commands](Commands.md).

## Optional: the block maker

`make:gm:cms-block` needs `symfony/maker-bundle` (dev):

```bash
composer require --dev symfony/maker-bundle
```
