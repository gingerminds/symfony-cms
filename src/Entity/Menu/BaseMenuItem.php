<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * An item of a menu tree (`menu_items`, as Laravel): its site is the one of the menu, deleting
 * a parent moves its children to the root. Serialized in the menu API (active items only).
 */
#[ORM\MappedSuperclass]
abstract class BaseMenuItem implements MenuItemInterface, EagerLoadableInterface, CacheCascadeInterface
{
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

    #[ORM\ManyToOne(targetEntity: MenuInterface::class, inversedBy: 'items')]
    #[ORM\JoinColumn(name: 'menu_id', nullable: false, onDelete: 'CASCADE')]
    protected ?MenuInterface $menu = null;

    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')]
    protected ?MenuItemInterface $parent = null;

    /**
     * @var Collection<int, MenuItemInterface>
     */
    #[ORM\OneToMany(targetEntity: MenuItemInterface::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    protected Collection $children;

    #[ORM\Column(name: 'is_active', options: ['default' => false])]
    protected bool $active = false;

    #[ORM\Column(options: ['default' => 0])]
    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    protected int $position = 0;

    #[ORM\Column(name: 'is_target_blank', options: ['default' => false])]
    protected bool $targetBlank = false;

    #[ORM\Column(name: 'is_no_referrer', options: ['default' => false])]
    protected bool $noReferrer = false;

    #[ORM\Column(name: 'is_no_opener', options: ['default' => false])]
    protected bool $noOpener = false;

    #[ORM\Column(name: 'is_no_follow', options: ['default' => false])]
    protected bool $noFollow = false;

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

    public function getMenu(): ?MenuInterface
    {
        return $this->menu;
    }

    public function setMenu(MenuInterface $menu): void
    {
        $this->menu = $menu;
        $this->site = $menu->getSite();

        if (!$menu->getItems()->contains($this)) {
            $menu->getItems()->add($this);
        }
    }

    public function getParent(): ?MenuItemInterface
    {
        return $this->parent;
    }

    public function setParent(?MenuItemInterface $parent): void
    {
        $this->parent?->getChildren()->removeElement($this);
        $this->parent = $parent;

        if ($parent instanceof MenuItemInterface && !$parent->getChildren()->contains($this)) {
            $parent->getChildren()->add($this);
        }
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    #[SerializedName('children')]
    public function getActiveChildren(): array
    {
        $items = $this->menu?->getItems() ?? $this->children;
        $children = array_filter($items->toArray(), fn (MenuItemInterface $item): bool => $item->isActive() && $item->getParent() === $this);

        return BaseMenu::sortItems($children);
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

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getName(): ?string
    {
        return $this->currentTranslation()?->getName();
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getUrl(): ?string
    {
        return $this->currentTranslation()?->getUrl();
    }

    #[Groups([BaseMenu::GROUP_LIST, BaseMenu::GROUP_READ])]
    public function getDescription(): ?string
    {
        return $this->currentTranslation()?->getDescription();
    }

    /**
     * The parent belongs to the same menu and is neither the item nor one of its descendants.
     */
    #[Assert\Callback]
    public function validateParent(ExecutionContextInterface $context): void
    {
        $parent = $this->parent;

        if (!$parent instanceof MenuItemInterface) {
            return;
        }

        if ($parent->getMenu() !== $this->menu) {
            $context->buildViolation('gingerminds_cms.menu_item.parent_other_menu')->atPath('parent')->addViolation();

            return;
        }

        for ($ancestor = $parent; $ancestor instanceof MenuItemInterface; $ancestor = $ancestor->getParent()) {
            if ($ancestor === $this) {
                $context->buildViolation('gingerminds_cms.menu_item.parent_cycle')->atPath('parent')->addViolation();

                return;
            }
        }
    }

    public function __toString(): string
    {
        return $this->getName() ?? (string) $this->code;
    }

    private function currentTranslation(): ?MenuItemTranslation
    {
        $translation = $this->getCurrentTranslation();

        return $translation instanceof MenuItemTranslation ? $translation : null;
    }
}
