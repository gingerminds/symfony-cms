<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\ApiPlatform\State;

use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Operation;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Page\PageFilterComputeService;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\CoreBundle\ApiPlatform\Filter\ComputedFilterStore;
use Gingerminds\CoreBundle\ApiPlatform\State\ResourceProvider;
use Gingerminds\CoreBundle\Model\FilterableInterface;
use Gingerminds\CoreBundle\Repository\ListQuery;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * The pages of the current site: the collection lists the published ones with their facets,
 * `by-slug` finds a published page by its URL path, `{id}` any page (front preview).
 *
 * @extends ResourceProvider<PageInterface&FilterableInterface>
 */
class PageProvider extends ResourceProvider
{
    public function __construct(
        protected readonly PageRepository $pages,
        RequestStack $requestStack,
        protected readonly SiteContext $siteContext,
        protected readonly LanguageContext $languageContext,
        protected readonly PageFilterComputeService $filters,
        protected readonly ComputedFilterStore $filterStore,
    ) {
        parent::__construct($pages, $requestStack);
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (isset($uriVariables['slug'])) {
            $siteId = $this->siteContext->id();
            $page = null !== $siteId ? $this->pages->findPublishedByPath((string) $uriVariables['slug'], $siteId, $this->languageContext->ids()) : null;

            return $page ?? throw new NotFoundHttpException('Page not found.');
        }

        return parent::provide($operation, $uriVariables, $context);
    }

    protected function configureListQuery(ListQuery $query, Operation $operation, array $uriVariables, array $context): ListQuery
    {
        if (!$operation instanceof CollectionOperationInterface) {
            return $query;
        }

        $query = $query->withFilter('status', PublicationStatus::PUBLISHED);
        /** @var class-string<PageInterface> $resourceClass the operation of a page resource */
        $resourceClass = $operation->getClass();
        $this->filterStore->set($resourceClass, $this->filters->compute($query));

        return $query;
    }
}
