# Plan de migration — portage de `gingerminds/laravel-cms`

Portage de `gingerminds/laravel-cms` vers `gingerminds/symfony-cms` (`Gingerminds\CmsBundle`, alias `gingerminds_cms`), même logique que core, media-manager et multisite.

## Méthode

Pour chaque étape : plan détaillé validé → implémentation → tests → `make phpunit`, `make phpstan`, `make fix-codestyle`, `make phpcs` → CHANGELOG `[Unreleased]` → commit `type(scope) …`.

- Étape 0 sur `master`, publication en **0.1.0**, installation dans `gingerminds-symfony-skeleton`.
- Étapes suivantes sur `build` (depuis `develop`), validées par MR (SonarQube + tests).

## Décisions

- Statut des pages : enum PHP `PageStatus` (`draft` / `published` / `archived`) + `symfony/workflow` pour les transitions (`published_at` / `archived_at`). Une base Laravel existante doit convertir ses noms de classe Spatie (requête documentée dans `docs/FromLaravel.md`).
- Dépendances : `gingerminds/symfony-core` ^1.9, `gingerminds/symfony-media-manager` ^1.1, `gingerminds/symfony-multisite` ^1.2.
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
| 4 | Pages (admin) : `Page` + `PageTranslation` (title, slug, hook, content JSON, visuel principal / vignette via `File`), statut enum + workflow, CRUD admin, filtres, modale « choisir une catégorie », synchro du slug (JS), bouton d'aperçu, libellé du statut, règle « page unique » des catégories (`PageCategoryUniquenessValidator`) | `build` | ⬜ |
| 5 | URL et API des pages : `PageUrl` + `PageUrlSyncer` (listener Doctrine), collisions d'URL, commande de resynchronisation ; API pages par id / chemin / code, filtres (équivalent `InjectPageFiltersMiddleware`) | `build` | ⬜ |
| 6 | WYSIWYG : `WysiwygType` + contrôleur Stimulus TipTap, presets configurables, assets via le slot `head` du core | `build` | ⬜ |
| 7a | Système de blocs : `BlockInterface` / `AbstractBlock`, registre (services tagués), formulaire depuis le schéma de champs, validation, aperçus Twig, `PageBlockController` (form / validate), répéteur, copie de structure entre langues, bloc TitleText, maker `make:gm:cms-block` | `build` | ⬜ |
| 7b | Blocs restants : Cards, FAQ, LinkList, Media, MediaList, Slider, TextImage, Video ; résolveurs File / Media, synchro des champs fichier avec le `FileReferenceRegistry` | `build` | ⬜ |
| 8 | Recherche : `search_index` + `search_index_categories`, `ExcludableFromSearch` / `PubliclySearchable`, indexation par listeners Doctrine, réindexation en cascade des catégories (Messenger), commande de réindexation, API recherche + résultats avec facettes | `build` | ⬜ |
| 9 | Documentation : README, `docs/` (Installation, Configuration, Menus, Pages, Blocks, Search, API, FromLaravel) | `build` | ⬜ |

Légende : ⬜ à faire · 🔄 en cours · ✅ commité

## Plus tard

- Étape 6 : remplacer le textarea de la description des éléments de menu par le `WysiwygType`.
- Étape 5 : resynchroniser les `PageUrl` à la modification / suppression d'une catégorie (Laravel `PageUrlSyncer::syncCategorySubtree()`).
- Étape 8 : effet de `is_hidden_from_search` des catégories (exclusion en cascade de leurs pages).
- Étape 9 (`FromLaravel.md`) : écarts volontaires des catégories — colonne `position` (ordre de secours : code), format du préfixe validé côté serveur, préfixe obligatoire si sous-catégories.
- Étape 9 (`FromLaravel.md`) : écarts volontaires des éléments de menu — parent limité au même menu et sans cycle, plus de champ « position » (glisser-déposer, fin de niveau), `is_no_follow` réellement validé ; `main_menu_id` dans l'API des sites.
