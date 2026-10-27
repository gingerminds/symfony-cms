<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

/**
 * Link options of a menu item (`target` and `rel` attributes of the front link).
 */
interface MenuItemLinkInterface
{
    public function isTargetBlank(): bool;

    public function setTargetBlank(bool $targetBlank): void;

    public function isNoReferrer(): bool;

    public function setNoReferrer(bool $noReferrer): void;

    public function isNoOpener(): bool;

    public function setNoOpener(bool $noOpener): void;

    public function isNoFollow(): bool;

    public function setNoFollow(bool $noFollow): void;
}
