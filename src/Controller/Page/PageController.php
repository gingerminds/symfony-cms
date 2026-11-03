<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Controller\AbstractCrudController;
use Gingerminds\CoreBundle\Model\TreeNodes;
use Gingerminds\CoreBundle\Pagination\Paginator;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Workflow\WorkflowInterface;

/**
 * Pages of the current site. "Add a page" first chooses the category (`?category_id=`).
 */
class PageController extends AbstractCrudController
{
    public function __construct(
        protected readonly SiteContext $siteContext,
        protected readonly PageCategoryRepository $categories,
        protected readonly WorkflowInterface $workflow,
    ) {
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

        $requestStack = $this->container->get('request_stack');
        \assert($requestStack instanceof RequestStack);
        $categoryId = $requestStack->getCurrentRequest()?->query->getString('category_id');
        $category = null !== $categoryId && '' !== $categoryId ? $this->categories->findOneForRead($categoryId) : null;
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
        return ['category_tree' => TreeNodes::flatten($this->categories->findTreeNodes())];
    }
}
