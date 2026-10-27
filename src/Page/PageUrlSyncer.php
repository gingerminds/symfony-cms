<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps the `page_urls` of a page (Laravel PageUrlSyncer): one path per translated language
 * (non blank title) while the page is published, none otherwise. A path taken by another published
 * page is never written: the page keeps its former path and the conflict is recorded.
 * Does not flush.
 */
class PageUrlSyncer implements ResetInterface
{
    /**
     * @var list<PageUrlConflict>
     */
    private array $conflicts = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function sync(PageInterface $page): void
    {
        $current = [];

        foreach ($this->entityManager->getRepository(PageUrl::class)->findBy(['page' => $page->getId()]) as $url) {
            $current[(int) $url->getLanguage()->getId()] = $url;
        }

        foreach ($page->isPublished() ? $this->paths($page) : [] as $languageId => [$language, $path]) {
            $url = $current[$languageId] ?? null;
            unset($current[$languageId]);

            if ($url?->getPath() === $path) {
                continue;
            }

            $owner = $this->owner($page, $language, $path);

            if ($owner instanceof PageInterface) {
                $this->conflicts[] = new PageUrlConflict($page, $language, $path, $owner);

                continue;
            }

            if ($url instanceof PageUrl) {
                $url->setPath($path);
            } elseif ($page->getSite() instanceof SiteInterface) {
                $url = new PageUrl($page, $language, $page->getSite(), $path);
                $page->getUrls()->add($url);
                $this->entityManager->persist($url);
            }
        }

        // The languages which no longer qualify (unpublished page, blank title).
        foreach ($current as $url) {
            $page->getUrls()->removeElement($url);
            $this->entityManager->remove($url);
        }
    }

    /**
     * The conflicts of the syncs since the last reset (the current request).
     *
     * @return list<PageUrlConflict>
     */
    public function getConflicts(): array
    {
        return $this->conflicts;
    }

    public function reset(): void
    {
        $this->conflicts = [];
    }

    /**
     * @return array<int, array{LanguageInterface, string}> language and path by language id
     */
    private function paths(PageInterface $page): array
    {
        $paths = [];

        foreach ($page->getTranslations() as $translation) {
            $language = $translation->getLanguage();

            if ($translation instanceof PageTranslation && $language instanceof LanguageInterface && '' !== (string) $translation->getTitle()) {
                $paths[(int) $language->getId()] = [$language, $page->getFullPath($language)];
            }
        }

        return $paths;
    }

    /**
     * The other page holding this path, if any.
     */
    private function owner(PageInterface $page, LanguageInterface $language, string $path): ?PageInterface
    {
        $url = $this->entityManager->getRepository(PageUrl::class)->findOneBy([
            'site' => $page->getSite()?->getId(),
            'language' => $language->getId(),
            'path' => $path,
        ]);

        return $url instanceof PageUrl && $url->getPage() !== $page ? $url->getPage() : null;
    }
}
