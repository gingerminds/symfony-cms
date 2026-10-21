<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Gingerminds\CoreBundle\Security\Voter\AbstractResourceVoter;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Page categories of the current site: a drag & drop tree.
 */
class PageCategoryController extends AbstractCrudController
{
    public const string REORDER_CSRF_TOKEN_ID = 'reorder-page-category';

    private ?string $parentId = null;

    public function __construct(
        CrudContext $context,
        protected readonly SiteContext $siteContext,
    ) {
        parent::__construct($context);
    }

    protected function getResourceName(): string
    {
        return 'page_category';
    }

    /**
     * The whole tree, without pagination nor filters.
     */
    public function index(Request $request): Response
    {
        $resource = $this->getResource();
        $this->denyAccessUnlessGranted(AbstractResourceVoter::VIEW, $resource->name);
        $children = $this->getCategoryRepository()->findTree();

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

        $this->getCategoryRepository()->reorder(is_numeric($parentId) ? (int) $parentId : null, $ids);

        return new JsonResponse(['success' => true]);
    }

    // The site is set before the validation: a category cannot be created without current site.
    protected function createEntity(): object
    {
        $category = parent::createEntity();

        if (!$category instanceof PageCategoryInterface) {
            return $category;
        }

        if ($this->siteContext->has()) {
            $category->setSite($this->siteContext->site());
        }

        $parent = null !== $this->parentId && is_numeric($this->parentId) ? $this->getCategoryRepository()->find((int) $this->parentId) : null;
        $category->setParent($parent instanceof PageCategoryInterface ? $parent : null);

        return $category;
    }

    protected function getCategoryRepository(): PageCategoryRepository
    {
        $repository = $this->getRepository();

        if (!$repository instanceof PageCategoryRepository) {
            throw new \LogicException(\sprintf('The repository of the page categories must extend "%s".', PageCategoryRepository::class));
        }

        return $repository;
    }
}
