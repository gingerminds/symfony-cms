<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslationInterface;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps the `page_urls` of pages (Laravel PageUrlSyncer): one path per translated language
 * (non blank title) while the page is published, none otherwise. A path taken by another published
 * page is never written: the page keeps its former path and the conflict is recorded.
 * Does not flush.
 */
class PageUrlSyncer implements ResetInterface
{
    private const int LOOKUP_CHUNK = 500;

    /**
     * @var list<PageUrlConflict>
     */
    private array $conflicts = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return list<PageUrl> the URLs created or changed (persisted, the removed ones removed)
     */
    public function sync(PageInterface ...$pages): array
    {
        $plans = array_map($this->plan(...), array_values($pages));
        $owners = $this->owners($this->wantedPaths($plans));
        $changed = [];

        foreach ($plans as [$page, $current, $paths]) {
            array_push($changed, ...$this->apply($page, $current, $paths, $owners));
        }

        return $changed;
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
     * The current URLs of a page and the paths it should have, by language id.
     *
     * @return array{PageInterface, array<int, PageUrl>, array<int, array{LanguageInterface, string}>}
     */
    private function plan(PageInterface $page): array
    {
        $current = [];

        foreach ($page->getUrls() as $url) {
            $current[(int) $url->getLanguage()->getId()] = $url;
        }

        return [$page, $current, $page->isPublished() ? $this->paths($page) : []];
    }

    /**
     * The paths to write, whose owner is looked up.
     *
     * @param list<array{PageInterface, array<int, PageUrl>, array<int, array{LanguageInterface, string}>}> $plans
     *
     * @return array<int, array<int, list<string>>> by site id and language id
     */
    private function wantedPaths(array $plans): array
    {
        $wanted = [];

        foreach ($plans as [$page, $current, $paths]) {
            foreach ($paths as $languageId => [, $path]) {
                if (($current[$languageId] ?? null)?->getPath() !== $path) {
                    $wanted[(int) $page->getSite()?->getId()][$languageId][] = $path;
                }
            }
        }

        return $wanted;
    }

    /**
     * Writes the paths of a page and removes the URLs of the languages which no longer qualify
     * (unpublished page, blank title).
     *
     * @param array<int, PageUrl>                          $current
     * @param array<int, array{LanguageInterface, string}> $paths
     * @param array<string, int|PageInterface>             $owners  page (id) holding each path, by key(); the paths taken here added
     *
     * @return list<PageUrl> the URLs created or changed
     */
    private function apply(PageInterface $page, array $current, array $paths, array &$owners): array
    {
        $changed = [];

        foreach ($paths as $languageId => [$language, $path]) {
            $url = $current[$languageId] ?? null;
            unset($current[$languageId]);

            if ($url?->getPath() === $path || !$this->claim($page, $language, $path, $owners)) {
                continue;
            }

            $url = $this->write($page, $url, $language, $path);

            if ($url instanceof PageUrl) {
                $changed[] = $url;
            }
        }

        foreach ($current as $url) {
            $page->getUrls()->removeElement($url);
            $this->entityManager->remove($url);
        }

        return $changed;
    }

    /**
     * Takes a path for a page unless another page holds it (a conflict is recorded): a second
     * page of the same sync asking for it is then in conflict.
     *
     * @param array<string, int|PageInterface> $owners
     */
    private function claim(PageInterface $page, LanguageInterface $language, string $path, array &$owners): bool
    {
        $key = $this->key($page->getSite(), (int) $language->getId(), $path);
        $owner = $owners[$key] ?? null;

        if (null !== $owner && $owner !== $page && $owner !== $page->getId()) {
            $this->conflicts[] = new PageUrlConflict($page, $language, $path, $owner instanceof PageInterface ? $owner : $this->page($page, $owner));

            return false;
        }

        $owners[$key] = $page;

        return true;
    }

    /**
     * The URL of a language set to a path, created when the page had none.
     */
    private function write(PageInterface $page, ?PageUrl $url, LanguageInterface $language, string $path): ?PageUrl
    {
        if ($url instanceof PageUrl) {
            $url->setPath($path);

            return $url;
        }

        $site = $page->getSite();

        if (!$site instanceof SiteInterface) {
            return null;
        }

        $url = new PageUrl($page, $language, $site, $path);
        $page->getUrls()->add($url);
        $this->entityManager->persist($url);

        return $url;
    }

    /**
     * @return array<int, array{LanguageInterface, string}> language and path by language id
     */
    private function paths(PageInterface $page): array
    {
        $paths = [];

        foreach ($page->getTranslations() as $translation) {
            $language = $translation->getLanguage();

            if ($translation instanceof PageTranslationInterface && $language instanceof LanguageInterface && '' !== (string) $translation->getTitle()) {
                $paths[(int) $language->getId()] = [$language, $page->getFullPath($language)];
            }
        }

        return $paths;
    }

    /**
     * The id of the page holding each of these paths, one query per site, language and chunk.
     *
     * @param array<int, array<int, list<string>>> $paths by site id and language id
     *
     * @return array<string, int> page id by key()
     */
    private function owners(array $paths): array
    {
        $owners = [];

        foreach ($paths as $siteId => $languages) {
            foreach ($languages as $languageId => $languagePaths) {
                foreach (array_chunk(array_values(array_unique($languagePaths)), self::LOOKUP_CHUNK) as $chunk) {
                    $rows = $this->entityManager->createQueryBuilder()
                        ->select('u.path AS path', 'IDENTITY(u.page) AS page')
                        ->from(PageUrl::class, 'u')
                        ->where('u.site = :site')
                        ->andWhere('u.language = :language')
                        ->andWhere('u.path IN (:paths)')
                        ->setParameter('site', $siteId)
                        ->setParameter('language', $languageId)
                        ->setParameter('paths', $chunk)
                        ->getQuery()
                        ->getArrayResult();

                    foreach ($rows as $row) {
                        $owners[$siteId . ':' . $languageId . ':' . $row['path']] = (int) $row['page'];
                    }
                }
            }
        }

        return $owners;
    }

    private function key(?SiteInterface $site, int $languageId, string $path): string
    {
        return (int) $site?->getId() . ':' . $languageId . ':' . $path;
    }

    /**
     * The other page holding a path (a conflict: rare, loaded on demand).
     */
    private function page(PageInterface $page, int $id): PageInterface
    {
        $owner = $this->entityManager->find($this->entityManager->getClassMetadata($page::class)->getName(), $id);

        return $owner instanceof PageInterface ? $owner : throw new \LogicException(\sprintf('The page %d holding a URL was not found.', $id));
    }
}
