# Refactoring — points à corriger

Issu de l'audit du 27/10/2026 (portage Laravel → Symfony). L'adaptation au core 1.10 est faite. Aucun projet n'utilise encore le bundle : les BC breaks sont acceptés.

Légende — effort : S (≤ ½ j), M (1–3 j), L (> 3 j) ; [H] hypothèse à vérifier.

## 3. Réimplémentations de ce que Symfony fait déjà

| Constat | Où | Natif | Effort |
|---|---|---|---|
| Système de blocs : un DSL (`BlockField`, trait de fabriques) traduit à la main en formulaire, contraintes, normalisation et revalidation du JSON | `src/Block/BlockFieldFactoryTrait.php`, `src/Form/Block/BlockDataType.php:121-152`, `src/Block/BlockConstraints.php`, `src/Block/BlockContentNormalizer.php`, `src/Validator/ValidBlocksValidator.php` | un `FormType` par bloc (`CollectionType` pour les répéteurs), revalidation en soumettant ce form ; extensions = `FormTypeExtension` | L |
| Recherche : ≈ 1 000 lignes pour un `LIKE '%x%'` sans index ; poids ignorés | `src/Search/Doctrine/DoctrineSearchEngine.php:20,136`, `config/definition.php:134` | MySQL `FULLTEXT` (MATCH … AGAINST avec poids) ou Meilisearch ; sinon retirer `weight` | M |
| Config du sanitizer construite à la main, nettoyage en `PRE_SUBMIT` | `src/Wysiwyg/WysiwygSanitizer.php:31-47`, `src/Form/Type/WysiwygType.php:39` | `framework.html_sanitizer.sanitizers.gingerminds_cms` (prepend) + option `sanitize_html` du `TextType` | S |
| Slug généré uniquement en JS | `assets/controllers/slug_sync_controller.js:57` | repli `AsciiSlugger` côté serveur | S |
| `BlockPass` qui gère « une sous-classe remplace le bloc » | `src/DependencyInjection/Compiler/BlockPass.php:43` | `tagged_locator(default_index_method: 'getKey')` | S |
| `beforeRemove` émule `ON DELETE SET NULL` déjà déclaré dans le mapping | `PageCategoryRepository::beforeRemove()`, `MenuItemRepository::beforeRemove()` | supprimer | S |
| Métadonnées des blocs en getters (`getKey()` statique, `getIcon()`, `getOrder()`, `getPreviewTemplate()`, `getTranslationDomain()`) + tag + `BlockPass` | `src/Block/BlockInterface.php`, `src/Block/Type/*`, `src/DependencyInjection/Compiler/BlockPass.php` | `#[AsContentBlock(key:, icon:, order:, template:)]` (comme `#[AsCrudController]`), la même `key` côté projet remplace le bloc ; avec la refacto des blocs | M |
| Types de la recherche publique déclarés en config (`search.resources`, prepend + validation dans `GingermindsCmsBundle::searchResources()`) | `src/GingermindsCmsBundle.php`, `config/definition.php` | `#[PublicSearchable(type:, label:, category:, group:)]` sur l'entité (hérité par une entité qui surcharge, comme `#[Cacheable]`) ; `PubliclySearchableInterface` garde le comportement ; à trancher avec le point recherche ci-dessus et `weight` (section 7) | M |
| Fonction Twig déclarée par `getFunctions()` | `src/Twig/PublicationExtension.php` | `#[AsTwigFunction]` (comme multisite et media-manager) | S |

## 4. Bugs et mauvais code

| Constat | Où | Correction | Effort |
|---|---|---|---|
| `flush()` relancé dans `postFlush`, qui redéclenche `SearchIndexListener` et `PageReferenceCacheListener` ; sous-arbre chargé sans limite ; 1 + N requêtes par page | `src/Doctrine/PageUrlListener.php:72,87`, `src/Page/PageUrlSyncer.php:37,111` | calcul en `onFlush` (`computeChangeSet`) ou message `SyncPageUrls` par lots | M |
| `ReindexSearchCategory` sans routage async : exécuté dans la requête ; indexation des contenus synchrone | `src/Doctrine/SearchIndexListener.php:80,85` | `#[AsMessage('async')]` + transport documenté | S |
| N+1 dans les résultats de recherche (`findBy` sans fetch-join) [H] | `src/Search/Api/SearchProvider.php` (`entities()`) | eager loads de la ressource | S |
| Toute modification d'un File / Media invalide toutes les réponses de pages | `src/Cache/PageReferenceCacheListener.php:43-59` | un tag par fichier posé à la résolution des références | M |
| `GET /api/pages/{id}` n'est plus mis en cache (opération avec `security`) | `src/Entity/Page/Page.php` | le front lit les pages publiques par `by-slug` | — |

## 5. Doublons avec les dépendances

- Traductions : `TranslationFieldsTrait` + relation propriétaire déclarée à la main refont `multisite/src/Model/TranslationTrait.php`.
- Slug unique : `UniqueSlugInCategory` ≈ `UniqueTranslationSlug` de multisite → option `scope` côté multisite.
- CSRF par en-tête refait à la main (1 endroit) → `#[IsCsrfTokenValid(…, tokenSource: SOURCE_HEADER)]`.

## 6. Surchargeabilité

| Blocage | Où | Piste | Effort |
|---|---|---|---|
| Impossible d'ajouter un champ traduit sans fork : `PageTranslation`, `MenuItemTranslation`, `PageCategoryTranslation` concrètes et absentes de `RESOURCES` | `PageTranslation.php:28`, `BasePage.php:80`, `PageTranslationType.php:83`, `UniqueSlugInCategoryValidator.php:58`, `UniqueSiblingPrefixValidator.php:58`, `config/services/block.php:88` | `Base*` + `resources.*.entity` | M |
| Surcharge de Page cassée côté traductions : `Page::class` / `PageCategory::class` en dur | `PageTranslation.php:70`, `PageCategoryTranslation.php:58` | interface + `resolve_target_entities` | S |
| Extensions tiptap fermées (enum de config, `private const`, imports JS figés) | `config/definition.php:26`, `WysiwygSanitizer.php:18`, `assets/controllers/wysiwyg_controller.js:65` | registre d'extensions par config + import dynamique | M |
| `PageUrl` codé en dur | `PageUrlSyncer.php:37`, `PageRepository.php:60` | `Base*` + config | S |
| `#[ApiResource]` sur l'entité concrète `Page` | `src/Entity/Page/Page.php` | ressource déclarée par config, même mécanisme que le core | M |
| `BlockRegistry`, `SearchIndexer`, `PageUrlSyncer` sans interface | `src/Block/BlockRegistry.php`, `src/Search/SearchIndexer.php`, `src/Page/PageUrlSyncer.php` | interfaces + `#[AsAlias]` si un besoin de décoration apparaît | S |

Sur-ingénierie à simplifier : les blocs cumulent quatre mécanismes d'extension (sous-classe qui remplace, `BlockExtensionInterface` jamais implémentée, `blocks.disabled`, `blocks.order`) : deux suffisent. `SearchEngineInterface` n'a qu'une implémentation (`search.engine` anticipe des moteurs prévus dans `TODO.md`).

## 7. Code mort

`BlockExtensionInterface` (aucune implémentation), `getApiStatus()` (doublon de `getStatus()`, `PageApiFieldsTrait.php:31`), option `search.*.weight` sans effet, `ExcludableSearchableInterface` (utilisée par `BasePage` seule).
