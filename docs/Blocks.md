# Content blocks

The `content` of a page translation is a list of blocks, stored as JSON:

```json
[
    {"uid": "8f2c...", "type": "title_text", "data": {"title": "Our history", "text": "<p>...</p>"}},
    {"uid": "1b7e...", "type": "media_list", "data": {"title": null, "items": [{"media": 12, "subtitle": "Red"}]}}
]
```

A block is a Symfony form type (its fields, their validation and their stored shape) with an
`#[AsContentBlock]` attribute (its key, icon, picker order and preview template). The editors compose the content in
a canvas ([`BlockCanvasType`](Forms.md#blockcanvastype)): add, edit, remove, reorder, copy the
structure of another language.

## Bundled blocks

| Key | Order | Fields |
|---|---|---|
| `title_text` | 0 | `title` (required), `text` (WYSIWYG `extended`: tables) |
| `text_image` | 100 | `title` (required), `image_position` (toggle: image on the right), `text` (WYSIWYG), `image` (file, required) |
| `media` | 150 | `title` (required), `file` (file of any type, required) |
| `video` | 200 | `title`, `embed_code` (required: a YouTube id or URL; `VideoType::youtubeId()` / `gm_cms_youtube_id()` extract the id) |
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

creates `App\Cms\Block\QuoteType` (key `quote`) and its preview `templates/cms/block/quote/preview.html.twig`
(see [Commands](Commands.md#makegmcms-block)). A block is any form type with `#[AsContentBlock]`
— autoconfigured, so nothing to register:

```php
namespace App\Cms\Block;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

#[AsContentBlock(
    key: 'quote',                                  // stored as `type`: never change it once pages use the block
    preview: 'cms/block/quote/preview.html.twig',
    icon: 'bi-quote',                              // Bootstrap Icons
    order: 250,                                    // picker weight (default 100)
)]
class QuoteType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('quote', TextareaType::class, ['constraints' => [new NotBlank()], 'size' => 'xl'])
            ->add('author', TextType::class, ['required' => false])
            ->add('style', ChoiceType::class, [
                'choices' => ['quote.style.light' => 'light', 'quote.style.dark' => 'dark'],
                'empty_data' => 'light',
            ])
            ->add('portrait', FilePickerType::class, ['required' => false, 'as_id' => true]);
    }
}
```

Two blocks with the same key fail when the registry is first used.

### Labels

In the domain of the block (`translationDomain`, `messages` by default):

- the block: `block.<key>.label` (or its `label`);
- a field: `block.<key>.field.<name>` (the `label_format` of the block form), unless it sets its
  own `label`; the fields of a row type set their own `label_format`
  (`block.cards.field.cards_%name%`, see `CardType`);
- the choices, `help`, `add_label` / `item_label` of a `BlockCollectionType`: translation keys too.

### Fields

Any form type whose data can be stored as JSON:

| Field | Form type | Stored value |
|---|---|---|
| text, textarea | `TextType`, `TextareaType` | string (`null` when empty) |
| rich text | [`WysiwygType`](Forms.md#wysiwygtype) (`preset`, `rows`) | sanitized HTML |
| select | `ChoiceType` | one of the values |
| toggle | `CheckboxType` (`label_attr: {class: checkbox-switch}`, `required: false`) | boolean |
| file of the media library | `FilePickerType` with `as_id: true` (`accept`: `image/*`, `[]`...) | file id (UUID) |
| media of the media picker | `MediaSelectType` with `as_id: true` (`multiple`, `categories`) | media id, a list with `multiple` |
| rows | `BlockCollectionType` (`entry_type`: a form type of the row, `add_label`, `item_label`) | list of objects |

- **Validation**: the `constraints` of the fields (`new NotBlank()`, `new Count(min: 1)` for rows,
  `new LinkUrl()`...), as in any form.
- **Defaults**: `empty_data` — the value of a new block, and of the blocks saved before the field
  existed (`ChoiceType`: `'light'`; a toggle is `false`, rows `[]`).
- **Layout**: the core `size` option (`tiny`, `sm`, `md`, `lg`, `xl`).

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

## Extending a bundled block

A form type extension changes the fields of a block, as of any form type:

```php
namespace App\Cms\Block;

use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

final class SubtitleExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TitleTextType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // After the title: `text` added back after it.
        $text = $builder->get('text');
        $builder->remove('text')->add('subtitle', TextType::class, ['required' => false])->add($text);
    }
}
```

The label of the added field is `block.title_text.field.subtitle` of the block domain
(`GingermindsCms`): give it its own `label` from your domain if needed.

## Replacing a bundled block

Replace its service (`gingerminds_cms.block.<key>`) by your form type, a child of the bundle one
(`getParent()`: its fields and their extensions) with its own `#[AsContentBlock]` of the same key:

```php
#[AsContentBlock(key: 'title_text', preview: 'cms/block/title_text/preview.html.twig', icon: 'bi-fonts', order: 0)]
final class TitleTextType extends AbstractType
{
    public function getParent(): string
    {
        return \Gingerminds\CmsBundle\Block\Type\TitleTextType::class;
    }
}
```

```yaml
# config/services.yaml
services:
    gingerminds_cms.block.title_text:
        class: App\Cms\Block\TitleTextType
        autoconfigure: true
```

## Editing and validation

- The block modal loads the form of a block (`POST /admin/pages/blocks/{key}/form`) and validates
  it (`POST .../validate`): the server returns the preview, or the form with its errors. Editing a
  block needs the create or edit permission of the pages.
- On save, each block of the content is submitted to its form: the data of the form is stored —
  the fields removed from a block are dropped, the new ones get their `empty_data`, toggles become
  booleans, rows are renumbered in their display order, the WYSIWYG HTML is sanitized again — and
  its errors are shown on the block (`ValidBlocks`), e.g. "Links 1 › URL: This value should not be
  blank.". A value that does not fit its field (an unknown choice) is kept for the error.
- A block whose type no longer exists (a removed block) is kept, never destroyed by a save,
  but reported as an error: remove it from the page, or restore its block, to save the page.
- An empty content is stored as `null`.

## Files and medias

The `FilePickerType` and `MediaSelectType` fields store ids: the files are picked in the media library, never uploaded
by the block. The files of the page contents are usages of the library
(`JsonFieldFileReferenceSource` on `page_translations.content`): a file used by a block cannot be
deleted, the library lists the page among its usages, and merging duplicates rewrites the ids in
the contents. A media used only by a block is not protected (no database integrity on JSON): its
reference becomes `null` in the API.

## API

`GET /api/pages/{id}` and `/api/pages/by-slug/{path}` serve the `content` with its references
resolved, one query per field type for the whole content (the reference fields of a block are
found once in its form, cached in `cache.system`):

- a file: `{id, url, thumbnail_url, mime_type, original_name, size, is_image}` (`thumbnail_url`:
  the `thumbnail` preset of an image, else `null`);
- a media: the media as in `GET /api/media` (`media:list` group, project medias included), a list
  when `multiple`;
- a missing file or media: `null` (removed from a list).

The stored content keeps the ids. A change of a file or of a media invalidates the cached page
responses.

### Another reference type

A form type of the project pointing at its own entities (its data: an id, a list of ids with
`multiple`) gets resolved by a `BlockReferenceResolverInterface` (autoconfigured), indexed by its form type with
`#[AsTaggedItem]` (the fields of a child type included):

```php
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: ProductPickerType::class)]
final readonly class ProductReferenceResolver implements BlockReferenceResolverInterface
{
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
