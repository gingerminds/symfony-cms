<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Page\PageFilterComputeService;
use Gingerminds\CoreBundle\ApiPlatform\Filter\ComputedFilterStore;

/**
 * The published pages of the current site with their facets: decorates the Doctrine collection
 * provider, the `status` parameter forced to `published`.
 *
 * @implements ProviderInterface<object>
 */
final readonly class PageCollectionProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<object> $collectionProvider
     */
    public function __construct(
        private ProviderInterface $collectionProvider,
        private PageFilterComputeService $filters,
        private ComputedFilterStore $filterStore,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $parameters = $operation->getParameters() ?? new Parameters();
        $parameters->get('status')?->setValue(PublicationStatus::PUBLISHED);
        $operation = $operation->withParameters($parameters);

        /** @var class-string $resourceClass */
        $resourceClass = $operation->getClass();
        $this->filterStore->set($resourceClass, $this->filters->compute($parameters));

        return $this->collectionProvider->provide($operation, $uriVariables, $context);
    }
}
