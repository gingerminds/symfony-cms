<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Menu;

use Gingerminds\CmsBundle\Controller\AbstractTreeCrudController;
use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Items of a menu (nested resource, `/admin/menus/{menuId}/items`): a drag & drop tree.
 */
class MenuItemController extends AbstractTreeCrudController
{
    private ?MenuInterface $menu = null;

    protected function getResourceName(): string
    {
        return 'menu_item';
    }

    protected function findTreeNodes(): array
    {
        return $this->getItemRepository()->findByMenu($this->getMenu());
    }

    protected function reorderLevel(?int $parentId, array $ids): void
    {
        $this->getItemRepository()->reorder($this->getMenu(), $parentId, $ids);
    }

    protected function createEntity(): object
    {
        $item = parent::createEntity();

        if ($item instanceof MenuItemInterface) {
            $item->setMenu($this->getMenu());
            $parent = null !== $this->parentId ? $this->findMenuItem($this->parentId) : null;
            $item->setParent($parent);
        }

        return $item;
    }

    protected function findEntity(string $id): object
    {
        return $this->findMenuItem($id)
            ?? throw new NotFoundHttpException(\sprintf('Menu item "%s" not found in this menu.', $id));
    }

    protected function getFormOptions(object $entity, bool $isNew): array
    {
        return ['menu' => $this->getMenu()];
    }

    protected function getCommonParameters(): array
    {
        return [...parent::getCommonParameters(), 'menu' => $this->getMenu()];
    }

    /**
     * The menu of the route, among those of the current site.
     */
    protected function getMenu(): MenuInterface
    {
        if ($this->menu instanceof MenuInterface) {
            return $this->menu;
        }

        $id = $this->getRouteParameters()['menuId'] ?? null;
        $menu = is_numeric($id) ? $this->context->doctrine->getRepository($this->context->resources->getEntityClass('menu'))->find((int) $id) : null;

        if (!$menu instanceof MenuInterface) {
            throw new NotFoundHttpException(\sprintf('Menu "%s" not found.', \is_scalar($id) ? (string) $id : ''));
        }

        return $this->menu = $menu;
    }

    protected function getItemRepository(): MenuItemRepository
    {
        $repository = $this->getRepository();

        if (!$repository instanceof MenuItemRepository) {
            throw new \LogicException(\sprintf('The repository of the menu items must extend "%s".', MenuItemRepository::class));
        }

        return $repository;
    }

    private function findMenuItem(string $id): ?MenuItemInterface
    {
        $item = is_numeric($id) ? $this->getItemRepository()->find((int) $id) : null;

        return $item instanceof MenuItemInterface && $item->getMenu() === $this->getMenu() ? $item : null;
    }
}
