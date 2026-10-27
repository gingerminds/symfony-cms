<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * An item of a menu tree (`menu_items`, as Laravel): its site is the one of the menu.
 * Serialized in the menu API (active items only).
 */
#[ORM\MappedSuperclass]
abstract class BaseMenuItem implements MenuItemInterface, EagerLoadableInterface, CacheCascadeInterface
{
    use MenuItemLinkTrait;
    use MenuItemTranslatedFieldsTrait;
    use MenuItemTreeTrait;
    use TimestampableTrait;
    use TranslatableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    protected ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    protected ?SiteInterface $site = null;

    #[ORM\Column(name: 'is_active', options: ['default' => false])]
    protected bool $active = false;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    protected int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public static function getTranslationEntityClass(): string
    {
        return MenuItemTranslation::class;
    }

    public static function getEagerLoads(): array
    {
        return [...self::getTranslationEagerLoads()];
    }

    // The menu cache embeds the item tree.
    public static function getCascadeCacheKeys(): array
    {
        return ['menu'];
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('is_active')]
    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function __toString(): string
    {
        return $this->getName() ?? (string) $this->code;
    }
}
