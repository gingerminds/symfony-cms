# Configuration

Everything is optional: the bundle works without a `gingerminds_cms` configuration. Full reference
with the defaults: `bin/console config:dump-reference gingerminds_cms`.

```yaml
# config/packages/gingerminds_cms.yaml
gingerminds_cms:
    resources:
        page:
            entity: App\Entity\Page        # extends BasePage
            controller: ~                  # extends PageController
            form: ~                        # extends PageType
        page_translation:
            entity: App\Entity\PageTranslation  # extends BasePageTranslation
    wysiwyg:
        presets:
            default: { extensions: [bold, italic, underline, strike, link, bulletList, orderedList, history] }
            news: { extensions: [bold, italic, link, heading] }
    slug_overwrite:
        create: true
        edit: false
    blocks:
        disabled: [slider]
        order: { faq: 5 }
    search:
        engine: doctrine
        message_threshold: 100
    publication:
        status_colors:
            draft: primary
            review: info
            published: success
            archived: warning
```

## Resources

`resources.menu`, `resources.menu_item`, `resources.page_category` and `resources.page` are
registered as [core resources](https://github.com/gingerminds/symfony-core/blob/master/docs/ResourceModel.md)
(admin CRUD, core `ResourceVoter` permissions, API cache), below the project
`gingerminds_core.resources`. Each takes:

| Key | Default | |
|---|---|---|
| `entity` | `Gingerminds\CmsBundle\Entity\…` | Your entity, extending the mapped superclass (`BaseMenu`, `BaseMenuItem`, `BasePageCategory`, `BasePage`). The relations of the bundle point at it (`resolve_target_entities`). |
| `controller` | the bundle one | Your admin controller, extending the bundle one. |
| `form` | the bundle one | Your admin form type. |
| `api` | `true` | `false` stops exposing the entity through the API. An entity overriding a bundle one inherits its `#[ApiResource]`. |

To add a few fields, prefer a form type extension and the form include slots (see below) to a new
form.

`resources.menu_item_translation`, `resources.page_category_translation` and
`resources.page_translation` take an `entity` only (no admin of their own): your translation,
extending `BaseMenuItemTranslation`, `BasePageCategoryTranslation` or `BasePageTranslation`, to add
a translated field. Restate the class-level attributes of the bundle entity (`#[ORM\Entity]`,
`#[ORM\Table]`, its `#[ORM\UniqueConstraint]`, `#[ORM\HasLifecycleCallbacks]`); the relations
point at it through its interface (`PageTranslationInterface`...) and the translation form types use
it as `data_class`. Add the field to the form with a type extension of `PageTranslationType`
(`MenuItemTranslationType`, `PageCategoryTranslationType`):

```php
#[ORM\Entity]
#[ORM\Table(name: 'page_translations')]
#[ORM\UniqueConstraint(name: 'page_translations_page_id_language_id_unique', columns: ['page_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
class PageTranslation extends BasePageTranslation
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    // getSubtitle(), setSubtitle()
}
```

## WYSIWYG presets

`wysiwyg.presets` names the toolbars of [`WysiwygType`](Forms.md#wysiwygtype). The entries, in
display order: `bold`, `italic`, `underline`, `strike`, `link`, `bulletList`, `orderedList`,
`blockquote`, `heading`, `horizontalRule`, `table`, `history` (undo / redo), or the name of an
entry added by the project ([TipTap extensions](Forms.md#tiptap-extensions)). Defaults:

| Preset | Extensions |
|---|---|
| `default` | bold, italic, underline, strike, link, bulletList, orderedList, history |
| `extended` | `default` + table |
| `minimal` | bold, italic, underline, link |
| `full` | bold, italic, strike, underline, link, bulletList, orderedList, blockquote, heading, horizontalRule, history |

Declaring `presets` replaces the defaults: keep a `default` preset (required) and the presets used
by the bundle (`minimal` for the page hook and the menu item description, `extended` for the
`title_text` block, `default` for the others) or override those fields.

## Slugs

`slug_overwrite.create|edit`: whether typing the title keeps overwriting the slug of a page (the
prefix of a category, from its name) on the create and on the edit forms. Without overwrite, a saved
or hand-typed slug is kept.

## Blocks

| Key | |
|---|---|
| `blocks.disabled` | Keys of the blocks hidden from the picker. The pages using them keep working. |
| `blocks.order` | Block key => picker weight (ascending), overriding the `order` of its `#[AsContentBlock]`. |

See [Blocks](Blocks.md).

## Search

| Key | Default | |
|---|---|---|
| `search.engine` | `doctrine` | `doctrine`, or the id of a service implementing `SearchEngineInterface`. The `search_index` tables are mapped only with `doctrine`. |
| `search.message_threshold` | `100` | Contents changed by a flush above which they are reindexed by `ReindexSearchContents` messages of this size (to route to an async transport) instead of during the request. |

The indexed types are the entities with a `#[SearchIndex]` (`BasePage`: `page`), not a configuration.
See [Search](Search.md#adding-a-content-type).

## Publication

`publication.status_colors`: the Bootstrap color of the status badge of each workflow place
(`secondary` for the others), including the places a project adds. See
[Pages](Pages.md#publication-workflow).

## Form include slots

The admin forms use the core form layout: each column ends with a `gingerminds_core.admin_includes`
slot, to add the fields of a form type extension or any template without overriding the form.

| Form | Slots |
|---|---|
| Menu | `menu_form_main`, `menu_form_sidebar` |
| Menu item | `menu_item_form_main`, `menu_item_form_sidebar`; per language `menu_item_translation_form_main`, `menu_item_translation_form_sidebar` |
| Page category | `page_category_form_main`, `page_category_form_sidebar`; per language `page_category_translation_form_main`, `page_category_translation_form_sidebar` |
| Page | `page_form_main`, `page_form_sidebar`; per language `page_translation_form_main`, `page_translation_form_sidebar` |

```yaml
# config/packages/gingerminds_core.yaml
gingerminds_core:
    admin_includes:
        page_form_sidebar: ['admin/page/_seo.html.twig']
```

The CMS itself adds the main menu to the site form through the multisite `site_form_sidebar` slot.
A field added by a type extension to a translation form, without include, is rendered at the end of
its language.
