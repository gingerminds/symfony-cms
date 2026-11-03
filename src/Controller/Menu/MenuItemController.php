<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CoreBundle\Controller\AbstractTreeCrudController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Items of a menu (nested resource, `/admin/menus/{menuId}/items`): a drag & drop tree.
 */
class MenuItemController extends AbstractTreeCrudController
{
    private ?MenuInterface $menu = null;

    public function entityBelongsToRoute(object $entity, array $routeParameters): bool
    {
        return $entity instanceof MenuItemInterface
            && $entity->getMenu() === $this->getMenu()
            && parent::entityBelongsToRoute($entity, $routeParameters);
    }

    protected function getTreeScope(): array
    {
        return ['menu' => $this->getMenu()];
    }

    protected function createEntity(): object
    {
        $item = parent::createEntity();

        if ($item instanceof MenuItemInterface) {
            $item->setMenu($this->getMenu());

            // The ?parent_id= of another menu.
            if ($item->getParent()?->getMenu() !== $this->getMenu()) {
                $item->setParent(null);
            }
        }

        return $item;
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
        $menu = is_numeric($id) ? $this->getDoctrine()->getRepository($this->getResourceRegistry()->getEntityClass('menu'))->find((int) $id) : null;

        if (!$menu instanceof MenuInterface) {
            throw new NotFoundHttpException(\sprintf('Menu "%s" not found.', \is_scalar($id) ? (string) $id : ''));
        }

        return $this->menu = $menu;
    }
}
