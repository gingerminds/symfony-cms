<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * The front preview URLs of a page, per language and front URL of its site (Laravel
 * `preview-button`): `{front}[/{iso} unless default language]/preview/page/{id}`.
 */
final class PreviewUrls
{
    /**
     * @return array<int, list<string>> URLs by language id, empty for a new page or a site without front URL
     */
    public function forPage(PageInterface $page, string $type = 'page'): array
    {
        $site = $page->getSite();

        if (null === $page->getId() || !$site instanceof SiteInterface || [] === $site->getFrontUrlValues()) {
            return [];
        }

        $default = $site->getDefaultLanguage();
        $urls = [];

        foreach ($site->getLanguages() as $language) {
            $localePath = $language === $default ? '' : '/' . $language->getIso();

            foreach ($site->getFrontUrlValues() as $frontUrl) {
                $urls[(int) $language->getId()][] = rtrim($frontUrl, '/') . $localePath . '/preview/' . $type . '/' . $page->getId();
            }
        }

        return $urls;
    }
}
