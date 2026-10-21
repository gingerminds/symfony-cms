<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Menu;

use Gingerminds\CmsBundle\Entity\Menu\MenuInterface;
use Gingerminds\CmsBundle\Entity\Menu\MenuItemInterface;
use Gingerminds\CmsBundle\Repository\Menu\MenuItemRepository;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Items of a menu (nested resource, `/admin/menus/{menuId}/items`): a drag & drop tree.
 */
class MenuItemController extends AbstractCrudController
{
    public const string REORDER_CSRF_TOKEN_ID = 'reorder-menu-item';

    private ?MenuInterface $menu = null;

    private ?string $parentId = null;

    protected function getResourceName(): string
    {
        return 'menu_item';
    }

    /**
     * The whole tree of the menu, without pagination nor filters.
     */
    public function index(Request $request): Response
    {
        $resource = $this->getResource();
        $this->denyAccessUnlessGranted(AbstractResourceVoter::VIEW, $resource->name);
        $menu = $this->getMenu();
        $children = [];

        foreach ($this->getItemRepository()->findByMenu($menu) as $item) {
            $children[$item->getParent()?->getId() ?? 0][] = $item;
        }

        return $this->render($resource->template('index'), [
            ...$this->getCommonParameters(),
            'roots' => $children[0] ?? [],
            'children' => $children,
            'reorder_csrf_token_id' => self::REORDER_CSRF_TOKEN_ID,
        ]);
    }

    /**
     * `?parent_id=` preselects the parent.
     */
    public function new(Request $request): Response
    {
        $this->parentId = $request->query->getString('parent_id') ?: null;

        return parent::new($request);
    }

    /**
     * Applies the order of one level of the tree.
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(AbstractResourceVoter::EDIT, $this->getResource()->name);

        if (!$this->isCsrfTokenValid(self::REORDER_CSRF_TOKEN_ID, $request->headers->get('X-CSRF-Token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $payload = $request->getPayload();
        $parentId = $payload->get('parent_id');
        $ids = array_values(array_map(intval(...), array_filter($payload->all('ids'), is_numeric(...))));

        $this->getItemRepository()->reorder($this->getMenu(), is_numeric($parentId) ? (int) $parentId : null, $ids);

        return new JsonResponse(['success' => true]);
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
