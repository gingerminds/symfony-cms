<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Search\ExcludableFromSearchInterface;
use Gingerminds\CmsBundle\Search\SearchEntry;

/**
 * A page in the public search: published, not hidden (itself or a category of its branch), one
 * entry per translation with a title, at its URL (`page_urls`, else its computed path).
 */
trait PageSearchTrait
{
    public function isPubliclyVisible(): bool
    {
        return PublicationStatus::PUBLISHED === $this->getStatus();
    }

    public function isExcludedFromSearch(): bool
    {
        $category = $this->getCategory();

        return $this->isHiddenFromSearch() || ($category instanceof ExcludableFromSearchInterface && $category->isExcludedFromSearch());
    }

    public function getSearchEntries(): iterable
    {
        foreach ($this->getTranslations() as $translation) {
            $language = $translation->getLanguage();

            if (!$translation instanceof PageTranslation || null === $language || '' === trim((string) $translation->getTitle())) {
                continue;
            }

            $url = null;

            foreach ($this->getUrls() as $pageUrl) {
                if ($pageUrl->getLanguage() === $language) {
                    $url = $pageUrl->getPath();
                }
            }

            yield new SearchEntry($language, (string) $translation->getTitle(), $url ?? $this->getFullPath($language), $translation->getHook(), $translation);
        }
    }
}
