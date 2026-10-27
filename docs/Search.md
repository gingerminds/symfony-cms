# Search

A public search across the content types of the site (pages, and the types a project adds), served
by `/api/search-indices` and `/api/search-results`. The bundle keeps a **search engine** in sync with
the contents; the engine is chosen by configuration:

- `doctrine` (default): two tables of the database, `LIKE` search, facets in SQL;
- any service implementing `SearchEngineInterface`: TNTSearch, Elasticsearch...

The admin search of a list (`filters[search]` on `/admin/pages`, `/api/pages`...) is another,
unrelated mechanism (core `SearchableInterface`).

## What is indexed

A content is indexed once per language, as a document: its title, URL and excerpt (the hit), the
text of its searched fields, its site, its publication date and its categories (the facets).

For the pages:

- only the `published` pages, unless the page or a category of its branch (itself or an ancestor)
  is hidden from search (`is_hidden_from_search`);
- one document per translation with a title, at its URL (`page_urls`);
- searched fields: `title` (weight 3) and `hook` (weight 1), without their HTML.

Change the searched fields in the configuration; they are property paths read on the translation,
else on the entity:

```yaml
gingerminds_cms:
    search:
        resources:
            page:
                fields:
                    hook: { weight: 0 }        # no longer searched
                    subtitle: { weight: 2 }    # a field added to the page translations
```

The weights are used by the engines able to rank (Elasticsearch, TNTSearch); the `doctrine` engine
searches every field alike.

## Keeping the index in sync

- After each flush, `SearchIndexListener` reindexes the contents changed (the content itself, one of
  its translations, or a `SearchOwnedInterface` entity such as its URLs) and removes the deleted
  ones, whatever made the change (admin, command, import).
- A category whose `hiddenFromSearch` or `parent` changed reindexes the contents of its branch
  through the `ReindexSearchCategory` Messenger message: synchronous by default, route it to an
  async transport for big categories ([Installation](Installation.md#7-messenger)).
- `bin/console gingerminds:cms:search:reindex [--type=page]` rebuilds the index: after the
  installation, an import, a change of the searched fields, or a new engine.

## Languages

The search runs in the current language. With `gingerminds_multisite.translation_fallback` (the
default), a content without a version in that language is found by its version in the fallback
language of the site — once per content, the current language first, as the page API shows it.
Without fallback, only the current language is searched.

## Adding a content type

1. Implement `PubliclySearchableInterface` (and `ExcludableFromSearchInterface` if it can be
   hidden) on the entity:

```php
use Gingerminds\CmsBundle\Search\ExcludableSearchableInterface;
use Gingerminds\CmsBundle\Search\SearchEntry;

class News implements ExcludableSearchableInterface // both interfaces
{
    public function isPubliclyVisible(): bool
    {
        return 'published' === $this->status;
    }

    public function isExcludedFromSearch(): bool
    {
        return $this->hiddenFromSearch || true === $this->category?->isExcludedFromSearch();
    }

    public function getSearchEntries(): iterable
    {
        foreach ($this->translations as $translation) {
            if ('' !== (string) $translation->getTitle()) {
                // Language, title, URL, excerpt, the object the configured fields are read on.
                yield new SearchEntry($translation->getLanguage(), $translation->getTitle(), 'news/' . $translation->getSlug(), $translation->getHook(), $translation);
            }
        }
    }
}
```

   The site comes from `SiteContextedInterface`, the publication date from `PublishableInterface`,
   when the entity implements them.

2. Declare the type:

```yaml
gingerminds_cms:
    search:
        resources:
            news:
                entity: App\Entity\News
                label: news.name_p             # facet label (translation key)
                translation_domain: messages
                category: category             # association: category facets, hidden categories
                group: news:list               # serialization of `resource` in /search-results
                fields:
                    title: { weight: 3 }
                    hook: ~
```

3. Index the existing contents: `bin/console gingerminds:cms:search:reindex --type=news`.

A category entity implements `ExcludableFromSearchInterface` (its flag and its ancestors) and exposes
`getChildren()` for its branch to be reindexed when it is hidden. An entity whose change must
reindex a content (other than its translations) implements `SearchOwnedInterface`.

## Writing an engine

```php
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResult;

final class ElasticsearchEngine implements SearchEngineInterface
{
    // The documents of a content, every language: replace the indexed ones.
    public function index(string $type, int $recordId, array $documents): void {}

    public function remove(string $type, int $recordId): void {}

    // Every document of a type (every type when null), before a full reindex.
    public function clear(?string $type = null): void {}

    public function search(SearchQuery $query): SearchResult {}
}
```

```yaml
gingerminds_cms:
    search:
        engine: App\Search\ElasticsearchEngine   # a service id
```

- A `SearchDocument` holds `type`, `recordId`, `siteId`, `languageId`, `title`, `url`, `excerpt`,
  `fields` (plain text by field) and `weights`, `publishedAt`, `categoryIds`; `text()` joins the
  fields.
- A `SearchQuery` holds `siteId`, `languageIds` (by priority: return one hit per content, in the
  first language it has), `text`, `types` and `categories` (type => ids, OR'd with the types),
  `page` and `itemsPerPage`.
- A `SearchResult` holds the `hits` (`SearchHit`) of the page, the `total`, and the facets:
  `typeCounts` (type => hits) and `categoryCounts` (type => category id => hits), counted against
  the text and the site, without the type filter.

Then run `gingerminds:cms:search:reindex`. With another engine, the `search_index` tables are not
mapped: drop them with a migration if they exist.

## The `doctrine` engine

`search_index` (one row per content and language: `type`, `record_id`, `site_id`, `language_id`,
`title`, `url`, `excerpt`, `searchable_text`, `published_at`) and `search_index_categories`
(`search_index_id`, `category_id`), as `gingerminds/laravel-cms`. The text is searched with `LIKE`
(case and accents as the database collation), the hits ordered by publication date, the most recent
first. Contents with integer ids only.

## API

See [API](API.md#search).
