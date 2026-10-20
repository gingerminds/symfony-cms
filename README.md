# Gingerminds CMS Bundle

Menus, pages, content blocks and search for Gingerminds Symfony admin panels, built on
[`gingerminds/symfony-core`](https://github.com/gingerminds/symfony-core),
[`gingerminds/symfony-media-manager`](https://github.com/gingerminds/symfony-media-manager) and
[`gingerminds/symfony-multisite`](https://github.com/gingerminds/symfony-multisite) — the Symfony 8
counterpart of `gingerminds/laravel-cms`.

Requires PHP 8.4, Symfony 8.1, Doctrine ORM 3, API Platform 4.4, `gingerminds/symfony-core` ^1.6,
`gingerminds/symfony-media-manager` ^1.0 and `gingerminds/symfony-multisite` ^1.0.

## Installation

```bash
composer require gingerminds/symfony-cms
```

Register the bundle and its routes:

```php
// config/bundles.php
Gingerminds\CmsBundle\GingermindsCmsBundle::class => ['all' => true],
```

```yaml
# config/routes/gingerminds_cms.yaml
gingerminds_cms:
    resource: '@GingermindsCmsBundle/config/routes.php'
```

## Development

```bash
make install       # composer install (Docker)
make assets        # importmap + sass of the test application
make qa            # phpstan, phpcs, phpunit
make serve         # test application on http://127.0.0.1:8000/admin
```

The test application (`tests/Application`) boots the core, media manager, multisite and CMS
bundles on SQLite, with an in-memory Flysystem storage. `APP_ENV=test php tests/Application/bin/console ...`.
