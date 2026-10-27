<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\Collection;
use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Model\LanguageScopedInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;

/**
 * Language scoped: the API serializes the item tree in the current language (`Accept-Language`).
 */
interface MenuInterface extends ResourceInterface, SiteContextedInterface, LanguageScopedInterface, TimestampableInterface, \Stringable
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;

    /**
     * Every item of the menu, ordered.
     *
     * @return Collection<int, MenuItemInterface>
     */
    public function getItems(): Collection;

    /**
     * The active root items, ordered (their active children: MenuItemInterface::getActiveChildren()).
     *
     * @return list<MenuItemInterface>
     */
    public function getActiveItems(): array;
}
