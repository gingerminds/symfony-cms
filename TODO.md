# Plan de migration — portage de `gingerminds/laravel-cms`

Portage de `gingerminds/laravel-cms` vers `gingerminds/symfony-cms` (`Gingerminds\CmsBundle`, alias `gingerminds_cms`), même logique que core, media-manager et multisite.

## Méthode

Pour chaque étape : plan détaillé validé → implémentation → tests → `make phpunit`, `make phpstan`, `make fix-codestyle`, `make phpcs` → CHANGELOG `[Unreleased]` → commit `type(scope) …`.

- Étape 0 sur `master`, publication en **0.1.0**, installation dans `gingerminds-symfony-skeleton`.
- Étapes suivantes sur `build` (depuis `develop`), validées par MR (SonarQube + tests).

## Décisions

- Statut des pages : chaîne pilotée par le workflow `gingerminds_cms_publication` (`symfony/workflow`, places `draft` / `published` / `archived` en constantes `PublicationStatus`), extensible par un projet (places et transitions ajoutées dans sa config) et réutilisable par toute ressource `PublishableInterface` (`PublicationStatusType`, badge `publication/_status.html.twig`, libellés `publication.status.*`). Une base Laravel existante doit convertir ses noms de classe Spatie (requête documentée dans `docs/FromLaravel.md`).
- Slug des pages unique par site, langue et catégorie (slug vide compris) : l'index Laravel (`site_id`, `language_id`, `slug`) n'est pas repris.
- Dépendances : `gingerminds/symfony-core` ^1.9, `gingerminds/symfony-media-manager` ^1.2, `gingerminds/symfony-multisite` ^1.2.
- Traductions dans l'API : option `gingerminds_multisite.translation_fallback` (multisite 1.2). `true` (défaut, comme Laravel) : repli sur la langue par défaut du site ; `false` : champs traduits à `null`, lignes conservées. Admin toujours avec repli. Chaque ressource traduite est testée dans les deux modes (environnement de test `test_no_fallback`).
- Ressources imbriquées (éléments d'un menu…) : `path: 'menus/{menuId}/items'`, le core 1.8 garde les paramètres de route dans les redirections et les templates (`getRouteParameters()`).
- Formulaires admin : layout du core (`@GingermindsCore/crud/_form_layout.html.twig`, blocs `main` / `sidebar`), chaque formulaire du bundle expose les slots `<ressource>_form_main` / `<ressource>_form_sidebar`. Les ajouts aux formulaires des autres bundles passent par leurs slots (ex. menu principal → `site_form_sidebar`).
- Même schéma de base que Laravel (tables, colonnes, index), porté par le mapping Doctrine. Pas de migrations dans le bundle.
- La migration de backfill `0027` (page_urls) devient une commande de resynchronisation des `PageUrl`.
- `git init` et remotes faits par l'utilisateur.
- Menu principal du site (ajout par rapport à Laravel) : colonne nullable `sites.main_menu_id`. Le projet déclare `gingerminds_multisite.resources.site.entity: Gingerminds\CmsBundle\Site\Entity\Site` (ou ajoute `SiteMainMenuTrait` à son propre Site). À documenter dans Installation et `FromLaravel.md` (étape 9).

## Étapes

| Étape | Contenu | Branche | État |
|---|---|---|---|
| 0 | Squelette : `composer.json`, `GingermindsCmsBundle`, arbre de config (`wysiwyg.presets`, `slug_overwrite`, `blocks.disabled` / `blocks.order` ; `resources.*` et `search_resources` arrivent avec leurs étapes), routes, application de test (core + media-manager + multisite + cms, SQLite), `ApiTestCase` / `Fixtures`, `BundleBootTest` / `ConfigurationTest`, CI, Makefile, outils QA, README, CHANGELOG, LICENSE | `master` → 0.1.0 | ✅ |
| 1 | Menus : entité `Menu` (site contexted, `site_id` NOT NULL), CRUD admin, voter, permissions, section « CMS » du menu admin ; menu principal du site (`SiteMainMenuTrait`, Site du CMS, champ du formulaire site) | `build` | ✅ |
| 2 | Éléments de menu : `MenuItem` + `MenuItemTranslation` (name, url, description, rel/target), ressource imbriquée `/admin/menus/{menuId}/items` (core 1.8), arbre drag & drop, `reorder`, invalidation du cache ; API `GET /api/menus` avec l'arbre des éléments actifs, `main_menu_id` dans `GET /api/sites` | `build` | ✅ |
| 3 | Catégories de pages : `PageCategory` en arbre (+ `position`, glisser-déposer), traductions (name, prefix au format slug, unique entre sœurs, obligatoire avec des sous-catégories), `is_unique` et `is_hidden_from_search` stockés, CRUD admin avec arbre, contrôleur Stimulus `gm-cms-slug-sync` | `build` | ✅ |
| 4 | Pages (admin) : `Page` + `PageTranslation` (title, slug unique par catégorie, hook, content, visuels via `FilePickerType`), workflow de publication extensible, CRUD admin, filtres, modale « choisir une catégorie », slug en input group et synchronisé, aperçu par langue, règle « page unique » des catégories | `build` | ✅ |
| 5 | URL et API des pages : `PageUrl` + `PageUrlSyncer` (listener Doctrine), collisions d'URL, commande de resynchronisation ; API pages par id / chemin / code, filtres (équivalent `InjectPageFiltersMiddleware`) | `build` | ✅ |
| 6 | WYSIWYG : `WysiwygType` + contrôleur Stimulus TipTap, presets configurables, assets via le slot `head` du core | `build` | ✅ |
| 7a | Système de blocs : `BlockInterface` / `AbstractBlock`, registre (services tagués), formulaire depuis le schéma de champs, validation, aperçus Twig, `BlockController` (form / validate), extensions de blocs, répéteur, copie de structure entre langues, bloc TitleText, maker `make:gm:cms-block` | `build` | ✅ |
| 7b | Blocs restants : Cards, FAQ, LinkList, Media, MediaList, Slider, TextImage, Video ; résolveurs File / Media, synchro des champs fichier avec le `FileReferenceRegistry` | `build` | ✅ |
| 8 | Recherche : `search_index` + `search_index_categories`, `ExcludableFromSearch` / `PubliclySearchable`, indexation par listeners Doctrine, réindexation en cascade des catégories (Messenger), commande de réindexation, API recherche + résultats avec facettes | `build` | ✅ |
| 9 | Documentation : README, `docs/` (Installation, Configuration, Menus, Pages, Blocks, Search, API, FromLaravel) | `build` | ✅ |

Légende : ⬜ à faire · 🔄 en cours · ✅ commité

## Plus tard

- Moteurs de recherche TNTSearch / Elasticsearch (implémentations de `SearchEngineInterface`, poids des champs).
