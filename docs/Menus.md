# Menus

A site has menus (header, footer...), each a tree of items translated in the languages of the site.

## Menus

`Menu` (`menus`, overridable `BaseMenu`, `resources.menu`): a `code` and its site (the current site
on creation). Admin: CMS › Menus (`/admin/menus`, `view|edit|delete menus`
permissions), the menus of the current site; "Items" opens the tree of a menu.

## Menu items

`MenuItem` (`menu_items`, overridable `BaseMenuItem`, `resources.menu_item`) and
`MenuItemTranslation` (`menu_item_translations`: `name`, `url`, `description` in the
[WYSIWYG](Forms.md#wysiwygtype) `minimal` preset):

| Field | |
|---|---|
| `code` | Identifier of the item. |
| `parent` | An item of the same menu, without cycle (validated). |
| `position` | Order in its level, set by drag & drop. |
| `is_active` | Only the active items are served by the API. |
| `is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow` | Link attributes for the front. |

Admin: `/admin/menus/{menuId}/items` (`view|edit|delete menu_items` permissions), a nested
[core resource](https://github.com/gingerminds/symfony-core/blob/master/docs/ResourceModel.md):

- the tree of the items, reordered by drag & drop within a level (`POST .../items/reorder`);
- "Add a child" on each item;
- General / Translations tabs;
- a new item, or an item moved to another parent, goes to the end of its level;
- deleting an item moves its children to the root.

## Main menu of a site

`SiteMainMenuInterface` / `SiteMainMenuTrait` add a `main_menu_id` (nullable, emptied when the
menu is deleted) to the site. The ready-made `Gingerminds\CmsBundle\Site\Entity\Site` has them
([Installation](Installation.md#3-the-site-entity)); with your own site entity:

```php
use Gingerminds\CmsBundle\Model\SiteMainMenuInterface;
use Gingerminds\CmsBundle\Model\SiteMainMenuTrait;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;

#[ORM\Entity]
#[ORM\Table(name: 'sites')]
class Site extends BaseSite implements SiteMainMenuInterface
{
    use SiteMainMenuTrait;
}
```

Override the site as described in the multisite
[Entities](https://github.com/gingerminds/symfony-multisite/blob/master/docs/Entities.md) documentation
(repository, API resource), then add the interface and the trait.

The site form gets a "Main menu" field (the menus of that site) in its right column, and
`GET /api/sites` a `main_menu_id`.

## API and cache

`GET /api/menus` and `GET /api/menus/{id}`: the menus of the current site with the tree of their
active items in the current language. See [API](API.md#menus).

The responses are cached under the `menu` key (24 hours); a change of a menu, of an item or of a
translation invalidates them.
