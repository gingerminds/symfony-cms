<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\Collection;
use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;

interface MenuItemInterface extends ResourceInterface, SiteContextedInterface, TranslatableInterface, TimestampableInterface, \Stringable
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;

    public function getMenu(): ?MenuInterface;

    /**
     * Also gives the item the site of the menu.
     */
    public function setMenu(MenuInterface $menu): void;

    public function getParent(): ?self;

    public function setParent(?self $parent): void;

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection;

    /**
     * The active children, ordered, among the items of the menu (no query once they are loaded).
     *
     * @return list<self>
     */
    public function getActiveChildren(): array;

    public function isActive(): bool;

    public function setActive(bool $active): void;

    public function getPosition(): int;

    public function setPosition(int $position): void;

    public function isTargetBlank(): bool;

    public function setTargetBlank(bool $targetBlank): void;

    public function isNoReferrer(): bool;

    public function setNoReferrer(bool $noReferrer): void;

    public function isNoOpener(): bool;

    public function setNoOpener(bool $noOpener): void;

    public function isNoFollow(): bool;

    public function setNoFollow(bool $noFollow): void;

    public function getName(): ?string;

    public function getUrl(): ?string;

    public function getDescription(): ?string;
}
