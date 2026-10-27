<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller;

use Gingerminds\CmsBundle\Model\TreeNodeInterface;
use Gingerminds\CmsBundle\Model\TreeNodes;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * A resource administered as a drag & drop tree (core `crud/list_tree.html.twig`): the index shows
 * the whole tree (`roots`, `children` by parent id), `?parent_id=` preselects the parent of a new
 * node (`$parentId`), `reorder` applies the order of one level.
 */
abstract class AbstractTreeCrudController extends AbstractCrudController
{
    protected ?string $parentId = null;

    public function index(Request $request): Response
    {
        $resource = $this->getResource();
        $this->denyAccessUnlessGranted(AbstractResourceVoter::VIEW, $resource->name);
        $children = TreeNodes::groupByParent($this->findTreeNodes());

        return $this->render($resource->template('index'), [
            ...$this->getCommonParameters(),
            'roots' => $children[0] ?? [],
            'children' => $children,
            'reorder_csrf_token_id' => $this->getReorderCsrfTokenId(),
        ]);
    }

    public function new(Request $request): Response
    {
        $this->parentId = $request->query->getString('parent_id') ?: null;

        return parent::new($request);
    }

    public function reorder(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted(AbstractResourceVoter::EDIT, $this->getResource()->name);

        if (!$this->isCsrfTokenValid($this->getReorderCsrfTokenId(), $request->headers->get('X-CSRF-Token'))) {
            throw new AccessDeniedHttpException('Invalid CSRF token.');
        }

        $payload = $request->getPayload();
        $parentId = $payload->get('parent_id');
        $ids = array_values(array_map(intval(...), array_filter($payload->all('ids'), is_numeric(...))));

        $this->reorderLevel(is_numeric($parentId) ? (int) $parentId : null, $ids);

        return new JsonResponse(['success' => true]);
    }

    /**
     * Every node of the tree, ordered within each level.
     *
     * @return list<TreeNodeInterface>
     */
    abstract protected function findTreeNodes(): array;

    /**
     * Applies the order of one level (`$parentId` null: the roots); ids of other levels are ignored.
     *
     * @param list<int> $ids
     */
    abstract protected function reorderLevel(?int $parentId, array $ids): void;

    protected function getReorderCsrfTokenId(): string
    {
        return 'reorder-' . str_replace('_', '-', $this->getResource()->name);
    }
}
