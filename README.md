# Gingerminds CMS Bundle

Menus, pages, content blocks and search for Gingerminds Symfony admin panels, built on
[`gingerminds/symfony-core`](https://github.com/gingerminds/symfony-core),
[`gingerminds/symfony-media-manager`](https://github.com/gingerminds/symfony-media-manager) and
[`gingerminds/symfony-multisite`](https://github.com/gingerminds/symfony-multisite) — the Symfony 8
counterpart of `gingerminds/laravel-cms`:

- **menus** of a site, trees of translated items, and the main menu of each site;
- **pages** in a tree of **categories** building their URLs, a **publication workflow** extensible by
  configuration, front preview links;
- **content blocks**: nine bundled blocks (text, images, medias, video, cards, slider, links, FAQ),
  your own in a class, overridden or extended, edited in a canvas with previews;
- a **WYSIWYG** form type (TipTap 3) with server-side sanitizing;
- a cross-content **search** with a pluggable engine (database by default, TNTSearch, Elasticsearch...)
  and facets;
- a public **API** of the menus, pages and search, by site and language.

Requires PHP 8.4, Symfony 8.1, Doctrine ORM 3, API Platform 4.4, `symfony/workflow`,
`symfony/messenger`, `symfony/html-sanitizer`, `gingerminds/symfony-core` ^1.9,
`gingerminds/symfony-media-manager` ^1.2 and `gingerminds/symfony-multisite` ^1.2.

## Installation

```bash
composer require gingerminds/symfony-cms
```

Then follow [Installation](docs/Installation.md) (bundle, routes, site entity, TipTap, database,
permissions).

## Documentation

**Getting started**

- [Installation](docs/Installation.md)
- [Configuration](docs/Configuration.md) — resources, WYSIWYG presets, blocks, search, publication, form include slots.
- [Commands](docs/Commands.md) — page URLs, search index, block maker.
- [Coming from laravel-cms](docs/FromLaravel.md) — concept mapping, behaviour changes, importing a Laravel database.

**Contents**

- [Menus](docs/Menus.md) — menus, items, main menu of a site.
- [Pages](docs/Pages.md) — categories, pages, publication workflow, URLs.
- [Blocks](docs/Blocks.md) — bundled blocks, writing, overriding and extending blocks, files and medias.
- [Forms](docs/Forms.md) — `WysiwygType`, `BlockCanvasType`, `PublicationStatusType`.
- [Search](docs/Search.md) — indexed contents, sync, adding a content type, writing an engine.

**API**

- [API](docs/API.md) — menus, pages, search.

## Development

```bash
make install       # composer install (Docker)
make assets        # importmap + sass of the test application
make qa            # phpstan, phpcs, phpunit
make serve         # test application on http://127.0.0.1:8000/admin
```

The test application (`tests/Application`) boots the core, media manager, multisite and CMS bundles
on SQLite, with an in-memory Flysystem storage, project blocks (a block with every field type, an
override and an extension of a bundled block) and an in-memory search engine.
`APP_ENV=test php tests/Application/bin/console ...`; the `test_no_fallback` environment disables
`translation_fallback`, `test_memory_search` uses the in-memory search engine.
