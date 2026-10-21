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

### Changed

- Requires `gingerminds/symfony-core` ^1.7, `gingerminds/symfony-media-manager` ^1.1 and
  `gingerminds/symfony-multisite` ^1.1 (form include slots).
