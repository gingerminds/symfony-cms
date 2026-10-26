<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * The translated fields of a page and its URL path.
 */
trait PageTranslatedFieldsTrait
{
    public function getTitle(): ?string
    {
        return $this->currentPageTranslation()?->getTitle();
    }

    public function getSlug(): ?string
    {
        return $this->currentPageTranslation()?->getSlug();
    }

    public function getHook(): ?string
    {
        return $this->currentPageTranslation()?->getHook();
    }

    public function getFullPath(LanguageInterface|int|null $language = null): string
    {
        $translation = null === $language ? $this->getCurrentTranslation() : $this->getTranslation($language, false);
        $languageId = $translation?->getLanguage()?->getId() ?? ($language instanceof LanguageInterface ? $language->getId() : $language);
        $slug = $translation instanceof PageTranslation ? (string) $translation->getSlug() : '';
        $categoryPath = $this->getCategory()?->getFullPath($languageId) ?? '';

        return implode('/', array_filter([$categoryPath, $slug], static fn (string $part): bool => '' !== $part));
    }

    private function currentPageTranslation(): ?PageTranslation
    {
        $translation = $this->getCurrentTranslation();

        return $translation instanceof PageTranslation ? $translation : null;
    }
}
