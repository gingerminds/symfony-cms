<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Controller\AbstractTreeCrudController;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;

/**
 * Page categories of the current site: a drag & drop tree.
 */
class PageCategoryController extends AbstractTreeCrudController
{
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

    protected function getCategoryRepository(): PageCategoryRepository
    {
        $repository = $this->getRepository();

        if (!$repository instanceof PageCategoryRepository) {
            throw new \LogicException(\sprintf('The repository of the page categories must extend "%s".', PageCategoryRepository::class));
        }

        return $repository;
    }
}
