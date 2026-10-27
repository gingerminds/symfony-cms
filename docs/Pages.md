# Pages

The pages of a site, organised in a tree of categories that builds their URLs, published through a
workflow, their content made of [blocks](Blocks.md).

## Page categories

`PageCategory` (`page_categories`, overridable `BasePageCategory`, `resources.page_category`) and
`PageCategoryTranslation` (`page_category_translations`: `name`, `prefix`):

| Field | |
|---|---|
| `code` | Unique per site. |
| `parent` | A category of the same site, without cycle. Deleting a category moves its children to the root. |
| `position` | Order in its level (drag & drop); fallback order: the code. |
| `is_unique` | The category holds one page only (`SinglePageCategory` on the page form). |
| `is_hidden_from_search` | Its pages, and the pages of its subcategories, are not found by the [search](Search.md). |
| `prefix` (per language) | URL segment of the category: lowercase letters, digits and hyphens, unique among the sibling categories of a language. Blank: the category adds nothing to the URL — refused when it has subcategories. |

`getFullPath(language)` gives the prefixes of the category and of its ancestors, the blank ones
skipped (`produits/tracteurs`).

Admin: CMS › Page categories (`/admin/page-categories`, `view|edit|delete page_categories`
permissions): the tree, drag & drop within a level, "Add a child", General / Translations tabs, the
prefix after the path of the parent and filled from the name (Stimulus `gm-cms-slug-sync`, see
[`slug_overwrite`](Configuration.md#slugs)).

## Pages

`Page` (`pages`, overridable `BasePage`, `resources.page`) and `PageTranslation`
(`page_translations`):

| Field | |
|---|---|
| `code` | Unique per site. |
| `category` | Optional (`null`: the root of the site). |
| `status`, `published_at`, `archived_at` | See the workflow below. |
| `is_hidden_from_search` | Not found by the [search](Search.md). |
| `main_visual`, `thumbnail` | Files of the media library (`FilePickerType`, images). |
| `title`, `slug` (per language) | The slug is unique among the pages of the same category in a language (`UniqueSlugInCategory`); blank: the home page of its category. |
| `hook` (per language) | Rich text ([WYSIWYG](Forms.md#wysiwygtype) `minimal` preset). |
| `content` (per language) | The [blocks](Blocks.md) of the page (JSON). |
| `main_visual`, `thumbnail` (per language) | Override the page visuals in this language. |

`getFullPath(language)` gives the category path and the slug (`produits/tracteurs/tracteur-x`).
The files of the visuals use `RESTRICT` foreign keys, and a page shows as a usage of its files in
the library (visuals and [block files](Blocks.md#files-and-medias)).

Admin: CMS › Pages (`/admin/pages`, `view|edit|delete pages` permissions):

- the list of the pages of the current site: title, category, status; filters on the publication
  date, the status and the category; search on the code and the title;
- "Add a page" chooses the category first (`/admin/pages/new?category_id=`);
- General tab: code, visuals, status, category, hidden from search;
- Translations tab, one pane per language: title, slug (after the category path, filled from the
  title), content blocks, hook, visuals and the front preview links.

The preview links are `{front URL}[/{iso}]/preview/page/{id}` for each front URL of the site (the
ISO prefix except for the default language): the front reads the page with `GET /api/pages/{id}`,
whatever its status.

## Publication workflow

The status of a page is a place of the `gingerminds_cms_publication` state machine
(`symfony/workflow`), shared with any entity implementing `PublishableInterface`
(`PublishableTrait`):

| Transition | From | To |
|---|---|---|
| `publish` | `draft` | `published` |
| `unpublish` | `published` | `draft` |
| `archive` | `published` | `archived` |
| `restore` | `archived` | `draft` |

`published_at` and `archived_at` are set when entering their place. The status field
(`PublicationStatusType`) offers the current status and the reachable ones, and applies the
transition on submit. A project adds its own places and transitions; they are merged with the
bundle ones:

```yaml
# config/packages/workflow.yaml
framework:
    workflows:
        gingerminds_cms_publication:
            places: [review]
            transitions:
                submit: { from: draft, to: review }
                approve: { from: review, to: published }

# config/packages/gingerminds_cms.yaml
gingerminds_cms:
    publication:
        status_colors: { review: info }
```

Label of a status: `publication.status.<place>` (GingermindsCms domain); badge:
`{% include '@GingermindsCms/publication/_status.html.twig' with {status: page.status} %}`, its
color from `gm_cms_status_color(status)`. Only the `published` pages are served by the page
collection API and found by the search.

## URLs

`PageUrl` (`page_urls`) holds the path of each language of a published page (the translations with
a title). A Doctrine listener keeps them up to date after any change of a page, of a translation or
of a category (its whole subtree, its deletion included), whoever made it.

A path already owned by another published page is not taken: the page keeps its former path and the
conflict is reported (a warning after saving or deleting a category, `UniquePageUrl` on the page
form). The front finds a page by its path with `GET /api/pages/by-slug/{path}`.

`gingerminds:cms:page-urls:sync` recomputes every path (after an import) and lists the conflicts:
see [Commands](Commands.md).

## Cache

The page API responses are cached under the `page` key. They are invalidated by a change of a page,
of a translation or of a category, and by a change of a file or of a media (the blocks embed
them).
