# Changelog

All notable changes to this bundle are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/).

## [Unreleased]

### Added

- `GingermindsCmsBundle` skeleton (`gingerminds_cms` configuration: `wysiwyg.presets`,
  `slug_overwrite`, `blocks.disabled` / `blocks.order`) and its test application booting the core,
  media manager and multisite bundles.
- `Menu` entity (overridable `BaseMenu` mapped superclass, `gingerminds_cms.resources.menu.*`):
  `menus` table as `gingerminds/laravel-cms` (`site_id` NOT NULL, the current site on creation),
  cached under the `menu` key for 24 hours.
- Admin CRUD of menus (core resource `menu`, `/admin/menus`, `view|edit|delete menus`
  permissions), listing the menus of the current site, in a new "CMS" section of the admin menu.
- Main menu of a site: `SiteMainMenuInterface` / `SiteMainMenuTrait` (`sites.main_menu_id`,
  emptied when the menu is deleted) and the ready-made `Gingerminds\CmsBundle\Site\Entity\Site`,
  mapped when declared as `gingerminds_multisite.resources.site.entity`. "Main menu" field of the
  site form, among the menus of that site, in its right column (`site_form_sidebar` form include).
- The menu form uses the core form layout (`menu_form_main|sidebar` form includes).
- Menu items: `MenuItem` (overridable `BaseMenuItem`, `gingerminds_cms.resources.menu_item.*`) and
  `MenuItemTranslation` (name, url, description), `menu_items` / `menu_item_translations` tables as
  `gingerminds/laravel-cms`. A change of an item or of a translation invalidates the `menu` cache.
- Admin tree of the items of a menu (nested resource `/admin/menus/{menuId}/items`, `view|edit|delete
  menu_items` permissions): drag & drop reordering of a level (`.../items/reorder`), "Add a child",
  General / Translations tabs (`menu_item_form_main|sidebar` form includes). The parent is chosen in
  the tree of the same menu, without the item nor its descendants (also validated); a new item, or
  one moved to another parent, goes to the end of its level; deleting an item moves its children to
  the root. "Items" button on the menu list.
- `GET /api/menus` and `GET /api/menus/{id}` (public, current site and language): `id`, `code` and
  `active_items`, the tree of the active items (`id`, `code`, `name`, `url`, `description`,
  `position`, `is_active`, `is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow`,
  `children`), documenting the `X-Site-Id` and `Accept-Language` headers. `main_menu_id` in
  `GET /api/sites` (CMS site). With `gingerminds_multisite.translation_fallback: false`, an
  untranslated item keeps its place with null `name`, `url` and `description`.
- Page categories: `PageCategory` (overridable `BasePageCategory`, `gingerminds_cms.resources.page_category.*`)
  and `PageCategoryTranslation` (name, URL prefix), `page_categories` / `page_category_translations`
  tables as `gingerminds/laravel-cms` plus `page_categories.position`. `getFullPath(language)`: the
  prefixes of the category and of its ancestors (blank ones skipped). `is_unique` (enforced on the
  pages later) and `is_hidden_from_search` (used by the search later) are already stored.
- Admin tree of the page categories (`/admin/page-categories`, `view|edit|delete page_categories`
  permissions, "CMS" admin menu section): drag & drop reordering of a level, "Add a child", General /
  Translations tabs (`page_category_form_main|sidebar` form includes), the prefix in an input group
  (`/<parent path>/` before, `/` after). Code unique per site; parent
  in the tree of the site, without cycle; prefix in the slug format (`a-z`, `0-9`, `-`), unique per
  language among the sibling categories (`UniqueSiblingPrefix`), blank allowed (stored as null)
  unless the category has sub-categories. Deleting a category moves its children to the root.
- `gm-cms-slug-sync` Stimulus controller (admin `head` include): fills a slug field from a source
  field, `slug_overwrite.create|edit` (without overwrite, a saved or hand typed slug is kept, the
  one it generated keeps following the source; the category prefix from its name); the letters without
  Unicode decomposition are transliterated (`œ` → `oe`, `ß` → `ss`...).
- Pages: `Page` (overridable `BasePage`, `gingerminds_cms.resources.page.*`) and `PageTranslation`
  (title, slug, hook, content, visuals overriding the page ones), `pages` / `page_translations` tables
  as `gingerminds/laravel-cms` but the `status` column (a string, see below) and the
  (`site_id`, `language_id`, `slug`) unique index (replaced by `UniqueSlugInCategory`: a slug, blank
  included, is unique per language among the pages of the same category). Visuals picked in the
  file library (`FilePickerType`), `files` foreign keys `RESTRICT`; a file used by a translation
  shows the page as its usage. `getFullPath(language)`: the category path and the slug.
- Publication workflow `gingerminds_cms_publication` (`symfony/workflow` state machine on
  `PublishableInterface` / `PublishableTrait`, reusable by any resource): `draft`, `published`,
  `archived` places (`PublicationStatus`), `publish` / `unpublish` / `archive` / `restore`
  transitions as Laravel, `published_at` / `archived_at` set on entering the places. A project adds
  its own places and transitions in its `framework.workflows.gingerminds_cms_publication`
  configuration (merged with the bundle one). `PublicationStatusType` (status among the enabled
  transitions, applied on submit), `@GingermindsCms/publication/_status.html.twig` badge
  (`gm_cms_status_color()`, `gingerminds_cms.publication.status_colors`), labels
  `publication.status.<place>`, `PublicationStatus::labels()` for the filters.
- Admin pages (`/admin/pages`, `view|edit|delete pages` permissions, first entry of the "CMS"
  section): list with title, category and status, publication date / status / category filters,
  search on the code and the title, "Add a page" choosing the category first (`?category_id=`).
  General / Translations tabs (`page_form_main|sidebar` form includes): status among the enabled
  transitions, category, visuals, slug in an input group after the category path and slugified
  from the title, front preview links per language and front URL. A category flagged `is_unique`
  holds one page (`SinglePageCategory`).
- Layout of each language of the translations (form theme `@GingermindsCms/form/translations_theme.html.twig`,
  `<type>_widget` blocks with the core form layout): main column and sidebar per language for the
  pages (preview, hook and visuals in the sidebar), `page_translation_form_main|sidebar`,
  `page_category_translation_form_*` and `menu_item_translation_form_*` form includes; the fields
  added without include are rendered at the end.
- Page URLs: `PageUrl` (`page_urls` table as `gingerminds/laravel-cms`), one path per language of a
  published page (translations with a title), kept up to date by a Doctrine listener on the changes
  of the pages, of their translations and of the categories (the whole subtree, deletion included).
  A path owned by another page is not taken: the former one is kept and the conflict reported (warning
  flash after saving or deleting a category). `UniquePageUrl` refuses such a path on the page form.
  `gingerminds:cms:page-urls:sync` command (backfill, lists the conflicts).
- Page API (public, current site and language, `X-Site-Id` / `Accept-Language` headers):
  `GET /api/pages` (published pages; `id`, `code`, `title`, `slug`, `hook`, `status`, `thumbnail`,
  `switch_lang`; `publishedAt` and `category` facets in `filters`, `filters[category]` filter),
  `GET /api/pages/{id}` (any status, front preview; plus `content` and `main_visual`),
  `GET /api/pages/by-slug/{path}` (published page by its full URL path). `switch_lang`:
  the stored path of each language for a published page, computed otherwise.
- `WysiwygType` (TipTap 3, Stimulus `gm-cms-wysiwyg`, form theme `@GingermindsCms/form/wysiwyg_theme.html.twig`):
  toolbar of a `gingerminds_cms.wysiwyg.presets` preset (`preset` option, unknown preset rejected),
  `rows` minimum height, labels translated (`wysiwyg.toolbar.*`). TipTap is loaded with the first
  editor of a page; the project importmap provides `@tiptap/core`, `@tiptap/starter-kit` and
  `@tiptap/extension-table`. The submitted HTML is sanitized (`WysiwygSanitizer`, `symfony/html-sanitizer`:
  the elements of the editor only, http(s) / mailto / tel / relative links), an empty editor gives null.
  Used by the page hook and the menu item description (`minimal` preset).
- Content blocks: `BlockInterface` / `AbstractBlock` (static key, label, Bootstrap icon, picker weight,
  Twig preview, translation domain) and their fields as immutable `BlockField` (text, textarea,
  WYSIWYG, select, toggle, repeater of one level; required, label, help, size, default, extra
  constraints). The blocks are autoconfigured services (`gingerminds_cms.block`) indexed by
  `BlockRegistry` (`blocks.disabled` / `blocks.order` for the picker): two unrelated blocks with the
  same key fail at container compilation, a block extending another one replaces it.
  `BlockExtensionInterface` services (`gingerminds_cms.block_extension`, by priority) change the
  fields of existing blocks. Bundled block: `title_text` (title, text with the `extended` preset).
- `BlockCanvasType` (Stimulus `gm-cms-blocks` and `gm-cms-repeater`, `sortablejs`): the blocks of a
  content (`[{uid, type, data}]`) with their server-rendered preview, add / edit / remove in modals,
  drag & drop, copy of the structure of another language. The block modal (`POST
  /admin/pages/blocks/{key}/form|validate`, page create or edit permission) renders `BlockDataType`
  and returns the preview or the form with its errors. On save the content is normalized (removed
  fields dropped, new ones defaulted, toggles as booleans, repeater rows renumbered, WYSIWYG HTML
  sanitized again, unknown blocks kept) and fully revalidated (`ValidBlocks`, errors shown on their
  block); an empty content gives null. Page translations get their `content` field.
- `file` (a file of the media library, `FilePickerType`, `accept` images by default) and `media`
  (`MediaSelectType`, `multiple`, `categories`) block fields, stored as ids. The files of the page
  contents are usages of the library (`JsonFieldFileReferenceSource` on `page_translations.content`):
  a used file cannot be deleted, a merge of duplicates rewrites the content. There is no direct
  upload nor automatic deletion of the files of the blocks.
- `ContentReferenceResolver` and `BlockReferenceResolverInterface` (autoconfigured, one per field
  type): the page `content` of the API serves the files (`id`, `url`, `thumbnail_url`, `mime_type`,
  `original_name`, `size`, `is_image`) and the medias (as `GET /api/media`) of its blocks, loaded once
  per type; a missing one is null. The previews get them as entities (`refs`). A change of a file or
  a media invalidates the cached page responses.
- Bundled blocks `text_image`, `media`, `video` (YouTube id or URL), `cards`, `slider`, `link_list`
  (absolute or relative URLs, `LinkUrl` constraint), `media_list` and `faq`, as `gingerminds/laravel-cms`.
- `make:gm:cms-block Name` (with `symfony/maker-bundle`): `App\Cms\Block\Name` (key in snake case) and
  its preview `templates/cms/block/<key>/preview.html.twig`, refused when the key is taken.

- Public search with a pluggable engine (`gingerminds_cms.search.engine`): `doctrine` (default, the
  `search_index` / `search_index_categories` tables of `gingerminds/laravel-cms`, mapped only for this
  engine, `LIKE` search, facets in SQL) or the id of a project service implementing
  `SearchEngineInterface` (TNTSearch, Elasticsearch...). The searchable types are declared in
  `gingerminds_cms.search.resources` (`page` by the bundle): entity, facet label, category
  association, serialization group, and the searched `fields` with their weight (page: `title`
  weight 3, `hook`). A type implements `PubliclySearchableInterface` (one `SearchEntry` per
  language: title, URL, excerpt) and `ExcludableFromSearchInterface`: a page is found when published
  and neither it nor a category of its branch is hidden from search.
- The search follows the contents after each flush (`SearchIndexListener`: the content, its
  translations, its URLs); a category hidden from search or shown again reindexes the contents of
  its branch through Messenger (`ReindexSearchCategory`, synchronous unless routed).
  `gingerminds:cms:search:reindex [--type=]` rebuilds the index. Requires `symfony/messenger` ^8.1.
- `GET /api/search-indices` (lean hits: `id`, `type`, `title`, `url`, `excerpt`, `published_at`) and
  `GET /api/search-results` (each hit with its content in `resource`, and the `type` facet: the types
  and their categories, `page` / `page:12`): `filters[search]`, `filters[type][]`, pagination, the
  current site, the current language then, with `translation_fallback`, the fallback one (one version
  per content).

### Changed

- CI: PHPUnit coverage (Xdebug, Clover report) sent to SonarQube, which now runs after the tests;
  line coverage shown on the merge requests. `assets/` analysed by SonarQube.
- Requires `symfony/workflow` ^8.1, `symfony/html-sanitizer` ^8.1, `gingerminds/symfony-core` ^1.9 (nested resources, input groups), `gingerminds/symfony-media-manager` ^1.2 (picker modals over the block modal) and
  `gingerminds/symfony-multisite` ^1.2 (form include slots, `translation_fallback`).
