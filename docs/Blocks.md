# Content blocks

The `content` of a page translation is a list of blocks, stored as JSON:

```json
[
    {"uid": "8f2c...", "type": "title_text", "data": {"title": "Our history", "text": "<p>...</p>"}},
    {"uid": "1b7e...", "type": "media_list", "data": {"title": null, "items": [{"media": 12, "subtitle": "Red"}]}}
]
```

A block is a small class describing its fields: the admin form, the validation and the stored
shape are generated from them; its preview is a Twig template. The editors compose the content in
a canvas ([`BlockCanvasType`](Forms.md#blockcanvastype)): add, edit, remove, reorder, copy the
structure of another language.

## Bundled blocks

| Key | Order | Fields |
|---|---|---|
| `title_text` | 0 | `title` (required), `text` (WYSIWYG `extended`: tables) |
| `text_image` | 100 | `title` (required), `image_position` (toggle: image on the right), `text` (WYSIWYG), `image` (file, required) |
| `media` | 150 | `title` (required), `file` (file of any type, required) |
| `video` | 200 | `title`, `embed_code` (required: a YouTube id or URL; `Video::youtubeId()` extracts the id) |
| `cards` | 300 | `title` (required), `text` (WYSIWYG), `cards` repeater: `title`, `description`, `image` (required) |
| `slider` | 350 | `title`, `slides` repeater: `image` (required) |
| `link_list` | 400 | `title` (required), `links` repeater: `label` (required), `url` (required, absolute `http(s)` URL or path of the site), `image` |
| `media_list` | 450 | `title`, `items` repeater: `media` (required), `subtitle` |
| `faq` | 500 | `title` (required), `items` repeater: `question` (required), `answer` (WYSIWYG, required) |

Hide some from the picker with `blocks.disabled`, reorder the picker with `blocks.order`
([Configuration](Configuration.md#blocks)): the pages using a disabled block keep it.

## Writing a block

```bash
bin/console make:gm:cms-block Quote
```

creates `App\Cms\Block\Quote` (key `quote`) and its preview `templates/cms/block/quote/preview.html.twig`
(see [Commands](Commands.md#makegmcms-block)). A block is any service implementing `BlockInterface`
— autoconfigured, so nothing to register:

```php
namespace App\Cms\Block;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

class Quote extends AbstractBlock
{
    // Stored as `type` in the contents: never change it once pages use the block.
    public static function getKey(): string
    {
        return 'quote';
    }

    public function getIcon(): string
    {
        return 'bi-quote'; // Bootstrap Icons
    }

    public function getOrder(): int
    {
        return 250; // picker weight (AbstractBlock: 100)
    }

    public function getFields(): array
    {
        return [
            BlockField::textarea('quote')->required(),
            BlockField::text('author'),
            BlockField::select('style', ['light' => 'quote.style.light', 'dark' => 'quote.style.dark'])->default('light'),
            BlockField::file('portrait'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return 'cms/block/quote/preview.html.twig';
    }

    public function getTranslationDomain(): string
    {
        return 'messages'; // AbstractBlock: GingermindsCms
    }
}
```

Two unrelated blocks with the same key fail at the container compilation
(`DuplicateBlockKeyException`).

### Labels

In the domain of the block (`getTranslationDomain()`):

- the block: `block.<key>.label` (`getLabel()`);
- a field: `block.<key>.field.<name>`, a repeater sub-field `block.<key>.field.<repeater>_<name>`
  (`block.cards.field.cards_title`), unless the field sets its own `->label()`;
- the choices of a select, `->help()`, `->addLabel()` / `->itemLabel()` of a repeater: translation
  keys too.

### Fields

`BlockField` is immutable: each method returns a copy.

| Constructor | Form field | Stored value |
|---|---|---|
| `BlockField::text($name)` | text input | string |
| `BlockField::textarea($name)` | textarea | string |
| `BlockField::wysiwyg($name)` | [`WysiwygType`](Forms.md#wysiwygtype) | sanitized HTML |
| `BlockField::select($name, [value => label])` | choice | one of the values |
| `BlockField::toggle($name)` | switch | boolean (default `false`) |
| `BlockField::file($name)` | file picked in the media library | file id (UUID) |
| `BlockField::media($name)` | media picked in the media picker | media id, a list with `->multiple()` |
| `BlockField::repeater($name, [fields])` | rows of sub-fields (one level: no repeater in a repeater), collapsible, reordered by drag & drop | list of objects |

| Method | |
|---|---|
| `->required()` | Not blank; a repeater needs a row, a toggle has always a value. |
| `->label($key)`, `->help($key)` | Translation keys of the block domain. |
| `->size('tiny'\|'sm'\|'md'\|'lg'\|'xl')` | Width of the field in the form (core sizes). |
| `->default($value)` | Value of a new block, and of the blocks saved before the field existed. |
| `->constraints(...$constraints)` | Extra validation, after the one of the type (e.g. `new LinkUrl()`). |
| `->preset($name)`, `->rows($lines)` | WYSIWYG toolbar and height (`default`, 8 lines). |
| `->accept([...])` | File: accepted mime types, exact or families (`image/*` by default, `[]`: every type). |
| `->multiple()`, `->categories([codes])` | Media: a list; codes of the allowed media categories. |
| `->addLabel($key)`, `->itemLabel($key)` | Repeater: "add" button, row title (`<label> <number>`). |

Always read the fields of a block through `BlockRegistry::getFields($block)`: it applies the
extensions.

### Preview

The Twig template of the admin preview (structural, not the site rendering) receives:

| Variable | |
|---|---|
| `block` | The block. |
| `data` | Its data, the defaults of the fields filled in. |
| `refs` | The same data, the files and medias replaced by their entity (`null` when missing). |
| `uid` | Its identifier. |

```twig
<div>
    {% if data.title %}<h3 class="h5">{{ data.title }}</h3>{% endif %}
    {% if refs.portrait %}<img src="{{ gm_file_url(refs.portrait, 'thumbnail') }}" alt="">{% endif %}
</div>
```

The bundle previews reuse `@GingermindsCms/block/_file.html.twig` (`file`, `missing`),
`_media.html.twig` (`media`, `missing`) and `_empty.html.twig`.

## Overriding a bundled block

A block extending another one with the same key replaces it (whatever their registration order):

```php
namespace App\Cms\Block;

use Gingerminds\CmsBundle\Block\Type\TitleText;

class TitleTextBlock extends TitleText
{
    public function getIcon(): string
    {
        return 'bi-fonts';
    }

    public function getPreviewTemplate(): string
    {
        return 'cms/block/title_text/preview.html.twig';
    }
}
```

## Extending blocks

To add or change fields of existing blocks without replacing them, implement
`BlockExtensionInterface` (autoconfigured, `gingerminds_cms.block_extension` tag): several
extensions can stack on a block, the highest priority first.

```php
namespace App\Cms\Block;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\Type\TitleText;

final class SubtitleExtension implements BlockExtensionInterface
{
    public static function getExtendedBlocks(): iterable
    {
        return [TitleText::getKey()];
    }

    public function buildFields(array $fields, BlockInterface $block): array
    {
        // After the title.
        return [$fields[0], BlockField::text('subtitle')->label('title_text.subtitle'), ...\array_slice($fields, 1)];
    }
}
```

An extension of an unknown block fails at the container compilation. The label of a field added by
an extension is translated in the domain of the block: give it its own `->label()` from your
domain if needed.

## Editing and validation

- The block modal loads the form of a block (`POST /admin/pages/blocks/{key}/form`) and validates
  it (`POST .../validate`): the server returns the preview, or the form with its errors. Editing a
  block needs the create or edit permission of the pages.
- On save, the whole content is normalized — the fields removed from a block are dropped, the new
  ones get their default, toggles become booleans, repeater rows are renumbered in their display
  order, the WYSIWYG HTML is sanitized again — and revalidated (`ValidBlocks`): an error is shown
  on its block, e.g. "Links 1 › URL: This value should not be blank.".
- A block whose type no longer exists (a removed block class) is kept, never destroyed by a save,
  but reported as an error: remove it from the page, or restore its block, to save the page.
- An empty content is stored as `null`.

## Files and medias

The `file` and `media` fields store ids: the files are picked in the media library, never uploaded
by the block. The files of the page contents are usages of the library
(`JsonFieldFileReferenceSource` on `page_translations.content`): a file used by a block cannot be
deleted, the library lists the page among its usages, and merging duplicates rewrites the ids in
the contents. A media used only by a block is not protected (no database integrity on JSON): its
reference becomes `null` in the API.

## API

`GET /api/pages/{id}` and `/api/pages/by-slug/{path}` serve the `content` with its references
resolved, one query per field type for the whole content:

- a `file`: `{id, url, thumbnail_url, mime_type, original_name, size, is_image}` (`thumbnail_url`:
  the `thumbnail` preset of an image, else `null`);
- a `media`: the media as in `GET /api/media` (`media:list` group, project medias included), a list
  when `multiple`;
- a missing file or media: `null` (removed from a list).

The stored content keeps the ids. A change of a file or of a media invalidates the cached page
responses.

### Another reference type

A field type of the project pointing at its own entities gets resolved by a
`BlockReferenceResolverInterface` (autoconfigured, indexed by `getFieldType()`):

```php
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;

final readonly class ProductReferenceResolver implements BlockReferenceResolverInterface
{
    public static function getFieldType(): string
    {
        return 'product';
    }

    public function load(array $ids): array
    {
        // One query for every id of the content, by id.
    }

    public function normalize(object $entity): array
    {
        return ['id' => $entity->getId(), 'name' => $entity->getName()];
    }
}
```

## Another block field

A resource of the project with its own [`BlockCanvasType`](Forms.md#blockcanvastype) field:

- the canvas, the modal and the validation work as for the pages (declare its two routes);
- serve the field resolved with `ContentReferenceResolver::resolve($content)` in its API
  (normalizer or provider);
- declare its file usages:

```yaml
# config/services.yaml
services:
    app.news_body_file_references:
        class: Gingerminds\MediaManagerBundle\File\Reference\JsonFieldFileReferenceSource
        arguments:
            $entityClass: App\Entity\NewsTranslation
            $field: body
```
