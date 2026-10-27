# Forms

Form types of the bundle, usable in any admin form of the project. Their form themes are
prepended to Twig and their Stimulus controllers registered on the core admin app: nothing to set
up.

## `WysiwygType`

Rich text edited with TipTap 3 (Stimulus `gm-cms-wysiwyg`), stored as HTML.

```php
use Gingerminds\CmsBundle\Form\Type\WysiwygType;

$builder->add('description', WysiwygType::class, [
    'preset' => 'minimal',
    'rows' => 8,
    'required' => false,
]);
```

| Option | Default | |
|---|---|---|
| `preset` | `default` | A toolbar of [`wysiwyg.presets`](Configuration.md#wysiwyg-presets); an unknown preset is an error. |
| `rows` | `6` | Minimum height of the editing area, in lines. |

The submitted HTML is sanitized on the server (`WysiwygSanitizer`, `symfony/html-sanitizer`): only
the elements the editor produces are kept (paragraphs, marks, links, lists, headings, quotes, rules,
tables), the links in `http(s)`, `mailto`, `tel` or relative; scripts, event attributes and
`javascript:` links are dropped, the content of other wrappers (`div`, `span`...) kept. An editor
without text gives `null`. The sanitizer is a service (`WysiwygSanitizer`) for other inputs.

TipTap is loaded with the first editor of a page, from the project importmap (see
[Installation](Installation.md#4-assets)). The toolbar labels are translated
(`wysiwyg.toolbar.*`, GingermindsCms domain).

## `BlockCanvasType`

A content made of [blocks](Blocks.md), stored as JSON (`[{uid, type, data}]`). The pages use it for
their `content`; another resource can use it on any JSON field.

```php
use Gingerminds\CmsBundle\Form\Block\BlockCanvasType;

$builder->add('body', BlockCanvasType::class, [
    'label' => 'news.field.body',
    'form_route' => 'app_news_block_form',
    'validate_route' => 'app_news_block_validate',
]);
```

| Option | Default | |
|---|---|---|
| `form_route` | `gingerminds_cms_page_block_form` | Route returning the form of a block. |
| `validate_route` | `gingerminds_cms_page_block_validate` | Route validating a block and returning its preview. |

The canvas (Stimulus `gm-cms-blocks`) shows the blocks with their preview, collapsed; it adds,
edits and removes them in modals, reorders them by drag & drop and copies the blocks of another
language of the same form. On submit the content is normalized and fully revalidated (`ValidBlocks`):
the errors are shown on their block. An empty canvas gives `null`, so an optional translation
without blocks stays blank.

The block modal routes of another resource point at the bundle controller, with the name of the
core resource whose create or edit permission they require:

```php
// config/routes.php
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    foreach (['form', 'validate'] as $action) {
        $routes->add('app_news_block_' . $action, '/admin/news/blocks/{key}/' . $action)
            ->controller('gingerminds_cms.controller.admin.block::' . $action)
            ->defaults(['resource' => 'news'])
            ->requirements(['key' => '[a-z0-9_]+'])
            ->methods(['POST']);
    }
};
```

For the API and the file usages of such a field, see [Blocks](Blocks.md#another-block-field).

## `PublicationStatusType`

The status of a `PublishableInterface` entity: its current place and the places reachable by an
enabled transition of its workflow; the transition is applied on submit (`published_at` /
`archived_at` set by the workflow). Not mapped: the workflow sets the status.

```php
use Gingerminds\CmsBundle\Form\Type\PublicationStatusType;

$builder->add('status', PublicationStatusType::class, ['subject' => $event]);
```

| Option | Default | |
|---|---|---|
| `subject` | required | The `PublishableInterface` entity. |
| `workflow` | `gingerminds_cms_publication` | The workflow of its status. |

See [Pages](Pages.md#publication-workflow).
