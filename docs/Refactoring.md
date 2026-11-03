# Refactoring — points à corriger

Issu de l'audit du 27/10/2026 (portage Laravel → Symfony). L'adaptation au core 1.10 est faite. Aucun projet n'utilise encore le bundle : les BC breaks sont acceptés.

Légende — effort : S (≤ ½ j), M (1–3 j), L (> 3 j) ; [H] hypothèse à vérifier.

## 6. Surchargeabilité

| Blocage | Où | Piste | Effort |
|---|---|---|---|
| Impossible d'ajouter un champ traduit sans fork : `PageTranslation`, `MenuItemTranslation`, `PageCategoryTranslation` concrètes et absentes de `RESOURCES` | `PageTranslation.php:28`, `BasePage.php:80`, `PageTranslationType.php:83`, `UniqueSlugInCategoryValidator.php:58`, `UniqueSiblingPrefixValidator.php:58`, `config/services/block.php:88` | `Base*` + `resources.*.entity` | M |
| Surcharge de Page cassée côté traductions : `Page::class` / `PageCategory::class` en dur | `PageTranslation.php:70`, `PageCategoryTranslation.php:58` | interface + `resolve_target_entities` | S |
| Extensions tiptap fermées (enum de config, imports JS figés ; le sanitizer se complète déjà par `framework.html_sanitizer`) | `config/definition.php:26`, `assets/controllers/wysiwyg_controller.js:65` | registre d'extensions par config + import dynamique | M |
| `PageUrl` codé en dur | `PageUrlSyncer.php:37`, `PageRepository.php:60` | `Base*` + config | S |
| `#[ApiResource]` sur l'entité concrète `Page` | `src/Entity/Page/Page.php` | ressource déclarée par config, même mécanisme que le core | M |
| `BlockRegistry`, `SearchIndexer`, `PageUrlSyncer` sans interface | `src/Block/BlockRegistry.php`, `src/Search/SearchIndexer.php`, `src/Page/PageUrlSyncer.php` | interfaces + `#[AsAlias]` si un besoin de décoration apparaît | S |

## 7. Code mort

`getApiStatus()` (doublon de `getStatus()`, `PageApiFieldsTrait.php:31`).
