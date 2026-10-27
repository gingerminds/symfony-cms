<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * Implements MenuItemLinkInterface (`is_target_blank`, `is_no_referrer`, `is_no_opener`, `is_no_follow`).
 */
trait MenuItemLinkTrait
{
    #[ORM\Column(name: 'is_target_blank', options: ['default' => false])]
    protected bool $targetBlank = false;

    #[ORM\Column(name: 'is_no_referrer', options: ['default' => false])]
    protected bool $noReferrer = false;

    #[ORM\Column(name: 'is_no_opener', options: ['default' => false])]
    protected bool $noOpener = false;

    #[ORM\Column(name: 'is_no_follow', options: ['default' => false])]
    protected bool $noFollow = false;

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('is_target_blank')]
    public function isTargetBlank(): bool
    {
        return $this->targetBlank;
    }

    public function setTargetBlank(bool $targetBlank): void
    {
        $this->targetBlank = $targetBlank;
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('is_no_referrer')]
    public function isNoReferrer(): bool
    {
        return $this->noReferrer;
    }

    public function setNoReferrer(bool $noReferrer): void
    {
        $this->noReferrer = $noReferrer;
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('is_no_opener')]
    public function isNoOpener(): bool
    {
        return $this->noOpener;
    }

    public function setNoOpener(bool $noOpener): void
    {
        $this->noOpener = $noOpener;
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('is_no_follow')]
    public function isNoFollow(): bool
    {
        return $this->noFollow;
    }

    public function setNoFollow(bool $noFollow): void
    {
        $this->noFollow = $noFollow;
    }
}
