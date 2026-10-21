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
  field, `slug_overwrite.create|edit` (the category prefix from its name).

### Changed

- CI: PHPUnit coverage (Xdebug, Clover report) sent to SonarQube, which now runs after the tests;
  line coverage shown on the merge requests. `assets/` analysed by SonarQube.
- Requires `gingerminds/symfony-core` ^1.9 (nested resources, input groups), `gingerminds/symfony-media-manager` ^1.1 and
  `gingerminds/symfony-multisite` ^1.2 (form include slots, `translation_fallback`).
