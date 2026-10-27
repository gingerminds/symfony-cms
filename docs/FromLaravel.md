# Coming from `gingerminds/laravel-cms`

The bundle keeps the Laravel package features — menus, page categories, pages and their URLs,
WYSIWYG, content blocks, cross-content search — and its database schema. This page maps each
concept, lists the deliberate behaviour changes and explains how to import a Laravel database. See
also the [core](https://github.com/gingerminds/symfony-core/blob/master/docs/FromLaravel.md),
[media manager](https://github.com/gingerminds/symfony-media-manager/blob/master/docs/FromLaravel.md)
and [multisite](https://github.com/gingerminds/symfony-multisite/blob/master/docs/FromLaravel.md)
pages.

## Concepts

| Laravel package | Symfony bundle | Notes |
|---|---|---|
| `LaravelCmsServiceProvider`, `...AuthServiceProvider` | `GingermindsCmsBundle` + `config/services/*.php` | `gingerminds_cms.*` service ids. |
| `config/gingerminds-cms.php` | `gingerminds_cms:` config | See the table below. |
| `ResourceResolver` (`resources.menu\|menu_item\|page\|page_category`) | `resources.*` (`entity`, `controller`, `form`) | Core resources, see [Configuration](Configuration.md#resources). |
| `Menu`, `MenuItem`, `PageCategory`, `Page` models + translations | `BaseMenu`, `BaseMenuItem`, `BasePageCategory`, `BasePage` mapped superclasses + entities | Overridable. |
| Policies | Voters | Same permission names. |
| Spatie model states (`State\Page\Status\*`, transitions) | `symfony/workflow` state machine `gingerminds_cms_publication` | Extensible by configuration, see [Pages](Pages.md#publication-workflow). |
| `<x-...::form.inputs.wysiwyg>` (TipTap 2) | [`WysiwygType`](Forms.md#wysiwygtype) (TipTap 3) | Same presets, same HTML. |
| `<x-...::form.inputs.canvas>`, `content-blocks.js` | [`BlockCanvasType`](Forms.md#blockcanvastype), Stimulus `gm-cms-blocks` | |
| `BlockInterface` (`key()`, `label()`, `icon()`, `order()`, `fields()`, `previewView()`) | `BlockInterface` (`static getKey()`, `getLabel()`, `getIcon()`, `getOrder()`, `getFields()`, `getPreviewTemplate()`, `getTranslationDomain()`) | See [Blocks](Blocks.md). |
| `fields()` arrays, `AbstractBlock::textField()`... | `BlockField::text()`... (immutable objects) | |
| `BlockRegistry` (static, folder scan) | `BlockRegistry` service, autoconfigured `gingerminds_cms.block` services | |
| `PageBlockController` (`GET form`, `POST validate`) | `BlockController` (`POST form`, `POST validate`) | |
| `BlockFieldValidator`, `ContentFieldSupport`, `HandlesContentBlocksTrait` | `BlockConstraints`, `BlockContentNormalizer`, `ValidBlocks` | |
| `ContentReferenceResolver`, `ReferenceFieldResolver`, `reference_resolvers` | `ContentReferenceResolver`, `BlockReferenceResolverInterface` (autoconfigured) | |
| `make:cms-block` | `make:gm:cms-block` | `App\Cms\Block\Name`, `templates/cms/block/<key>/`. |
| `SearchIndex` model, `SearchIndexer`, observers | `SearchEngineInterface`, `SearchIndexer`, `SearchIndexListener` | Pluggable engine, see [Search](Search.md). |
| `PubliclySearchableInterface::getPublicSearchTranslations()` | `PubliclySearchableInterface::getSearchEntries()` (`SearchEntry`) | The searched text comes from the configured `fields`. |
| `ExcludableFromSearchInterface` + trait | `ExcludableFromSearchInterface` | |
| `ReindexCategoryItemsJob` (queue) | `ReindexSearchCategory` message (Messenger) | |
| `search:reindex` | `gingerminds:cms:search:reindex` | |
| Migration `0027` (backfill of `page_urls`) | `gingerminds:cms:page-urls:sync` | |
| Migrations shipped by the package | `doctrine:migrations:diff` in the project | |

### Configuration

| Laravel `gingerminds-cms.*` | Symfony `gingerminds_cms.*` |
|---|---|
| `resources.<name>.model` | `resources.<name>.entity` |
| `resources.<name>.controller` / `request` | `resources.<name>.controller` / `form` |
| `wysiwyg.presets` | `wysiwyg.presets` (same) |
| `slug_overwrite` | `slug_overwrite` (same) |
| `block_paths` | — (autoconfigured services) |
| `blocks` (override by key) | — (a block extending the bundle one replaces it) |
| `disabled_blocks`, `block_order` | `blocks.disabled`, `blocks.order` |
| `reference_resolvers` | — (`BlockReferenceResolverInterface` services) |
| `search_resources.<type>` (`model`, `label`, `category_relation`, `category_model`, `group`) | `search.resources.<type>` (`entity`, `label`, `category`, `group`, `fields`) |
| — | `search.engine`, `publication.status_colors` |

## Behaviour changes

### Menus

- The parent of an item is chosen in the tree of the same menu, without the item nor its
  descendants (validated).
- No "position" field: the items are reordered by drag & drop; a new item, or one moved to another
  parent, goes to the end of its level.
- `is_no_follow` is really validated.
- `main_menu_id` of a site (new): `sites.main_menu_id`, exposed by `GET /api/sites`.

### Page categories

- `position` column (drag & drop); fallback order: the code.
- The prefix format (`a-z`, `0-9`, `-`) is validated on the server; a prefix is required for a
  category with subcategories, unique among the siblings.

### Pages

- `status` is a string holding the workflow place (`draft`, `published`...), no longer a Spatie
  class name: convert it on import (see below).
- The unique index (`site_id`, `language_id`, `slug`) is replaced by the uniqueness of a slug among
  the pages of the same category (`UniqueSlugInCategory`).
- The `files` foreign keys of the visuals are `RESTRICT`: a used file cannot be deleted.
- The front preview links are in the right column of each language.

### URLs and page API

- `filters[category]` instead of `category_id`.
- The collection serves the published pages only; `/api/pages/{id}` serves any status for the
  preview.
- The URL sync is tolerant: a path taken by another page keeps the former path and reports the
  conflict instead of failing the save.
- `switch_lang` is read from `page_urls` for a published page.

### WYSIWYG

- TipTap 3 instead of 2: the produced HTML is the same.
- The HTML is sanitized on the server; an empty editor is stored as `null`.

### Content blocks

- Blocks are autoconfigured services (no `block_paths` scan), a bundled block is overridden by
  extending it (no `blocks` mapping), block extensions are new.
- Fields are `BlockField` objects; labels `block.<key>.field.<name>` and
  `block.<key>.field.<repeater>_<name>` for a sub-field.
- The key is static (`getKey()`).
- The modal endpoints are `POST` (the data of the edited block in the body, not the query string);
  the block form errors are rendered by the server.
- The WYSIWYG HTML of the blocks is sanitized again on save; an empty content is stored as `null`.
- The copy of the structure of another language is a single modal (language, warning when the
  target has blocks).
- `file` fields pick a file in the media library: no direct upload, and no automatic deletion of
  the orphan files (a used file is protected; clean up with `gingerminds:media:files:orphans`).
- The medias of the blocks are serialized as `GET /api/media` (`media:list` group).
- `link_list` accepts site paths (`/contact`) besides absolute URLs.
- A change of a file or a media invalidates the cached page responses.

### Search

- Pluggable engine (`search.engine`); `doctrine` keeps the Laravel tables.
- The searched fields and their weight are configured; the pages search the title and the hook
  (Laravel: the title only).
- A category hidden from search hides the pages of its subcategories too.
- With `translation_fallback`, a content without a version in the current language is found in the
  fallback language.
- The `id` of a hit is the id of the content (Laravel: the id of the index row).
- `/search-results` `resource`: the content as in its own list, with its `@id`.

## Importing a Laravel database

The schema is the same: point the Symfony application at a copy of the database, then mark the
existing migrations as executed and generate the remaining differences (`sites.main_menu_id`,
`page_categories.position`, the columns of the other bundles) with `doctrine:migrations:diff`. Then:

1. Convert the page statuses (Spatie class names) to workflow places (MySQL):

```sql
UPDATE pages SET status = CASE status
    WHEN 'Gingerminds\\LaravelCms\\State\\Page\\Status\\Draft' THEN 'draft'
    WHEN 'Gingerminds\\LaravelCms\\State\\Page\\Status\\Published' THEN 'published'
    WHEN 'Gingerminds\\LaravelCms\\State\\Page\\Status\\Archived' THEN 'archived'
    ELSE status
END;
```

   A project with its own Laravel states maps them to its own places the same way.

2. Recompute the URLs, rebuild the search index, sync the permissions:

```bash
bin/console gingerminds:cms:page-urls:sync
bin/console gingerminds:cms:search:reindex
bin/console gingerminds:permissions:sync
```

The block contents are compatible (`[{uid, type, data}]`, same keys and field names): the `file`
fields hold `files` ids, the `media` fields `medias` ids. A project block is rewritten as a
`BlockInterface` class with the same key.
