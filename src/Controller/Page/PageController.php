<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\TreeNodes;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Controller\CrudContext;
use Gingerminds\CoreBundle\Pagination\Paginator;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Pages of the current site. "Add a page" first chooses the category (`?category_id=`).
 */
class PageController extends AbstractCrudController
{
    private ?string $categoryId = null;

    public function __construct(
        CrudContext $context,
        protected readonly SiteContext $siteContext,
        protected readonly PageCategoryRepository $categories,
        protected readonly WorkflowInterface $workflow,
    ) {
        parent::__construct($context);
    }

    protected function getResourceName(): string
    {
        return 'page';
    }

    public function new(Request $request): Response
    {
        $this->categoryId = $request->query->getString('category_id') ?: null;

        return parent::new($request);
    }

    // The site is set before the validation: a page cannot be created without current site.
    protected function createEntity(): object
    {
        $page = parent::createEntity();

        if (!$page instanceof PageInterface) {
            return $page;
        }

        if ($this->siteContext->has()) {
            $page->setSite($this->siteContext->site());
        }

        $category = null !== $this->categoryId && is_numeric($this->categoryId) ? $this->categories->find((int) $this->categoryId) : null;
        $page->setCategory($category instanceof PageCategoryInterface ? $category : null);

        return $page;
    }

    /**
     * The status filter offers every place of the workflow, the project ones included.
     */
    protected function getCommonParameters(): array
    {
        $parameters = parent::getCommonParameters();
        $parameters['filter_configs']['status']['choices'] = PublicationStatus::labels($this->workflow->getDefinition()->getPlaces());

        return $parameters;
    }

    /**
     * @param Paginator<object> $items
     */
    protected function getIndexParameters(Request $request, Paginator $items): array
    {
        return ['category_tree' => TreeNodes::flatten($this->categories->findTree())];
    }
}
