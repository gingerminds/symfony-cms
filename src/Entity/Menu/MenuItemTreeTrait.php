<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * The place of a menu item in its menu tree: menu (and its site), parent, children. Both sides of
 * the relations are kept in sync. Deleting a parent moves its children to the root.
 */
trait MenuItemTreeTrait
{
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

    public function getMenu(): ?MenuInterface
    {
        return $this->menu;
    }

    public function setMenu(MenuInterface $menu): void
    {
        $this->menu = $menu;
        $this->setSite($menu->getSite());

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
}
