# API

Public endpoints of the bundle, under the API prefix of the core (`/api`), in JSON-LD (or JSON with
`Accept: application/json`). The lists take the core list parameters: `page`, `itemsPerPage`,
`order[<property>]=asc|desc`, `search=`, and a parameter per filter (`category=4`, `category[]=4`,
`publishedAt[after]=2025-01-01`); an invalid value is a `422`.

## Context

The contents are those of the current site and language, resolved by the multisite bundle from the
`X-Site-Id` (site id or code) and `Accept-Language` headers (or the host), documented on every
operation. With `gingerminds_multisite.translation_fallback` (default), a missing translation falls
back to the fallback language of the site; without it, the translated fields of an untranslated
content are `null` and it keeps its place. See the multisite
[API](https://github.com/gingerminds/symfony-multisite/blob/master/docs/API.md).

Null values are omitted from the responses.

## Menus

`GET /api/menus` and `GET /api/menus/{id}`:

```json
{
    "@id": "/api/menus/1",
    "@type": "Menu",
    "id": 1,
    "code": "header",
    "active_items": [
        {
            "id": 3,
            "code": "products",
            "name": "Products",
            "url": "/products",
            "description": "<p>...</p>",
            "position": 0,
            "is_active": true,
            "is_target_blank": false,
            "is_no_referrer": false,
            "is_no_opener": false,
            "is_no_follow": false,
            "children": []
        }
    ]
}
```

`active_items` is the tree of the active items, in their order. Cached under the `menu` key.

`GET /api/sites` (CMS site entity) adds the `main_menu_id` of each site.

## Pages

| Endpoint | |
|---|---|
| `GET /api/pages` | The published pages: `id`, `code`, `title`, `slug`, `hook`, `status`, `thumbnail` (file id), `switch_lang`. |
| `GET /api/pages/{id}` | A page, plus `content` and `main_visual` (file id). Published: public; any other status (front preview): `view pages` permission (bearer token), else 401 anonymous / 403 without it. |
| `GET /api/pages/by-slug/{path}` | A published page by its full URL path in the current language (`produits/tracteurs/tracteur-x`, without leading slash; the home page of a category: its path). 404 otherwise. |

- `switch_lang`: the path of the page in each language (ISO => path), to switch language on the
  front: the stored paths of a published page, computed for another status.
- `content`: the [blocks](Blocks.md) of the translation, their files and medias resolved
  ([Blocks API](Blocks.md#api)).
- `thumbnail`, `main_visual`: the visual of the translation, else the page one. Build the URLs
  with `/api/files/{id}` and `/api/files/{id}/{preset}` (media manager).

Parameters of the collection: `search` (code, title), `category` (a category id, a list `[]=`, or
`null`), `publishedAt[after]` / `publishedAt[before]` (`Y-m-d`), `order[code|status|publishedAt]`.
The JSON-LD response carries the facets in `filters`:

```json
{
    "member": [ ... ],
    "totalItems": 12,
    "filters": {
        "publishedAt": {"type": "date", "options": {"min": "2025-01-14", "max": "2026-03-01", "years": [2025, 2026]}},
        "category": {"type": "relation", "options": [{"value": 4, "label": "Products", "total": 7}]}
    }
}
```

Each facet is counted against the other active filters. The page responses are cached under the
`page` key (invalidated by a change of a page, a translation, a category, a file or a media), except
`GET /api/pages/{id}`: secured by the page, it is never cached (the front reads the public pages
by `by-slug`, the id is for the preview).

The collection, `by-slug` and the [search](#search) only serve published pages, token or not
(`status` cannot widen the list). A project entity overriding the page (`resources.page.entity`)
inherits the bundle `#[ApiResource]`; if it declares its own, keep the item security,

```php
new Get(security: self::API_ITEM_SECURITY), // BasePage: published, or `is_granted('VIEW', object)`
```

Without `security`, the bundle provider does not find an unpublished page by id (404).

## Search

| Endpoint | |
|---|---|
| `GET /api/search-indices` | Lean hits, for an autocomplete: `id` (of the content), `type`, `title`, `url`, `excerpt`, `published_at`. |
| `GET /api/search-results` | The same hits, each with its content in `resource` (normalized with the `group` of its type: a page as in `/api/pages`), and the `type` facet in `filters`. |

Parameters:

- `search`: the searched text;
- `type[]`: a type (`page`) or a category of a type (`page:12`, the values of the facet
  options); several values are OR'd; unknown types are ignored;
- `page`, `itemsPerPage` (10 by default, 100 at most).

```
GET /api/search-results?search=tractor&type[]=page:4
```

```json
{
    "member": [
        {
            "id": 7,
            "type": "page",
            "title": "Red tractor",
            "url": "products/red-tractor",
            "published_at": "2026-03-01T10:00:00+00:00",
            "resource": {"@id": "/api/pages/7", "@type": "Page", "id": 7, "code": "tractor", "title": "Red tractor", "...": "..."}
        }
    ],
    "totalItems": 1,
    "filters": {
        "type": {
            "type": "facet",
            "multiple": true,
            "options": [
                {"value": "page", "label": "Pages", "total": 2, "group": null},
                {"value": "page:4", "label": "Products", "total": 1, "group": "page"},
                {"value": "page:9", "label": "News", "total": 1, "group": "page"}
            ]
        }
    }
}
```

The facet lists each type with hits, then its categories with hits (`group`: the type); a selected
option is kept at `0`. Counts apply the searched text, not the type filter. The front sends back the
`value` of an option as is. The site and language rules are those of the [search](Search.md#languages).
The search responses are not cached.
