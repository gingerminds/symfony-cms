<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Controller\AbstractTreeCrudController;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Page categories of the current site: a drag & drop tree.
 */
class PageCategoryController extends AbstractTreeCrudController
{
    public function __construct(
        CrudContext $context,
        protected readonly SiteContext $siteContext,
        protected readonly PageUrlSyncer $urlSyncer,
    ) {
        parent::__construct($context);
    }

    protected function getResourceName(): string
    {
        return 'page_category';
    }

    protected function findTreeNodes(): array
    {
        return $this->getCategoryRepository()->findTree();
    }

    protected function reorderLevel(?int $parentId, array $ids): void
    {
        $this->getCategoryRepository()->reorder($parentId, $ids);
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

    public function delete(Request $request, string $id): Response
    {
        $response = parent::delete($request, $id);
        $this->flashUrlConflicts($request);

        return $response;
    }

    protected function redirectAfterSave(object $entity, bool $isNew): Response
    {
        $request = $this->context->requestStack?->getCurrentRequest();

        if (null !== $request) {
            $this->flashUrlConflicts($request);
        }

        return parent::redirectAfterSave($entity, $isNew);
    }

    /**
     * A page whose new URL path is taken by another page keeps its former one (PageUrlSyncer).
     */
    protected function flashUrlConflicts(Request $request): void
    {
        foreach ($this->urlSyncer->getConflicts() as $conflict) {
            $this->addFlash($request, 'warning', $this->trans('page_category.flash.url_conflict', [
                '%page%' => $conflict->page->getCode(),
                '%language%' => $conflict->language->getIso(),
                '%path%' => '/' . $conflict->path,
                '%owner%' => $conflict->owner->getCode(),
            ], GingermindsCmsBundle::TRANSLATION_DOMAIN));
        }
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
