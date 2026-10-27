# Commands

| Command | |
|---|---|
| `gingerminds:cms:page-urls:sync` | Recomputes the URL paths of every page. |
| `gingerminds:cms:search:reindex [--type=]` | Rebuilds the search index. |
| `make:gm:cms-block <Name>` | Creates a content block and its preview. |

## `gingerminds:cms:page-urls:sync`

```bash
bin/console gingerminds:cms:page-urls:sync
```

Recomputes the `page_urls` of every page (a path per language of a published page), by batches.
Needed once after an import (the Laravel backfill migration `0027`); afterwards a Doctrine
listener keeps them up to date. A path already owned by another page is not taken: the page keeps
its former path and the command lists these conflicts (page, language, path, owner) to fix the
slugs. See [Pages](Pages.md#urls).

## `gingerminds:cms:search:reindex`

```bash
bin/console gingerminds:cms:search:reindex                         # every type
bin/console gingerminds:cms:search:reindex --type=page --type=news  # some types
```

Empties the index of each type and indexes its contents again, by batches, then prints how many are
indexed (the unpublished or hidden contents are not). An unknown type is reported and skipped. Run
it after the installation, an import, a change of the searched fields or of the engine. See
[Search](Search.md).

## `make:gm:cms-block`

```bash
bin/console make:gm:cms-block Quote
```

Needs `symfony/maker-bundle`. Creates:

- `src/Cms/Block/Quote.php` (`App\Cms\Block\Quote`), key `quote` (the class name in snake case), an
  example `title` field, labels in the `messages` domain;
- `templates/cms/block/quote/preview.html.twig`, its admin preview.

The block is in the picker at once (autoconfigured service). The command refuses an existing class
and a key already used by another block. Then declare its fields, its preview and its translations
(`block.quote.label`, `block.quote.field.title`...). See [Blocks](Blocks.md#writing-a-block).
