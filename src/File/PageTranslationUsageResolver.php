<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\File;

use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MediaManagerBundle\File\Reference\FileReference;
use Gingerminds\MediaManagerBundle\File\Reference\FileUsage;
use Gingerminds\MediaManagerBundle\File\Reference\FileUsageResolverInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * A file used by a page translation (visuals): the page, with its edit link.
 */
final readonly class PageTranslationUsageResolver implements FileUsageResolverInterface
{
    public function __construct(
        private ResourceRegistry $resources,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
    ) {
    }

    public function resolve(FileReference $reference): ?FileUsage
    {
        $owner = $reference->owner;
        $page = $owner instanceof PageTranslation ? $owner->getTranslatable() : null;

        if (!$owner instanceof PageTranslation || !$page instanceof PageInterface) {
            return null;
        }

        $resource = $this->resources->get('page');

        return new FileUsage(
            $this->translator->trans($resource->translationKey('name_s'), [], $resource->translationDomain),
            \sprintf('%s (%s)', $owner->getTitle() ?? $page->getCode(), $owner->getLanguage()?->getIso()),
            $this->urlGenerator->generate($resource->route('edit'), ['id' => $page->getId()]),
        );
    }
}
