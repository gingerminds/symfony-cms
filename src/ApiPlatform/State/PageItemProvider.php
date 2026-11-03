<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\ApiPlatform\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Gingerminds\MultisiteBundle\Context\LanguageContext;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * A page of the current site: `by-slug` the published page of a URL path, `{id}` the Doctrine
 * item provider, an unpublished page only through an operation with `security` (front preview).
 *
 * @implements ProviderInterface<object>
 */
final readonly class PageItemProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<object> $itemProvider
     */
    public function __construct(
        private ProviderInterface $itemProvider,
        private PageRepository $pages,
        private SiteContext $siteContext,
        private LanguageContext $languageContext,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        if (isset($uriVariables['slug'])) {
            $siteId = $this->siteContext->id();
            $page = null !== $siteId ? $this->pages->findPublishedByPath((string) $uriVariables['slug'], $siteId, $this->languageContext->ids()) : null;

            return $page ?? throw new NotFoundHttpException('Page not found.');
        }

        $page = $this->itemProvider->provide($operation, $uriVariables, $context);

        return $page instanceof PageInterface && !$page->isPublished() && null === $operation->getSecurity() ? null : $page;
    }
}
