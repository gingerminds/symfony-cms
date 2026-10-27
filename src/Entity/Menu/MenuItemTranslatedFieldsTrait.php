<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Symfony\Component\Serializer\Attribute\Groups;

/**
 * The translated fields of a menu item, in the current language (null without translation).
 */
trait MenuItemTranslatedFieldsTrait
{
    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getName(): ?string
    {
        return $this->currentMenuItemTranslation()?->getName();
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getUrl(): ?string
    {
        return $this->currentMenuItemTranslation()?->getUrl();
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getDescription(): ?string
    {
        return $this->currentMenuItemTranslation()?->getDescription();
    }

    private function currentMenuItemTranslation(): ?MenuItemTranslation
    {
        $translation = $this->getCurrentTranslation();

        return $translation instanceof MenuItemTranslation ? $translation : null;
    }
}
