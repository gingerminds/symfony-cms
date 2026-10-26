<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * The page API fields beyond the translated ones (Laravel `Page` API properties).
 */
trait PageApiFieldsTrait
{
    /**
     * @var Collection<int, PageUrl>
     */
    #[ORM\OneToMany(targetEntity: PageUrl::class, mappedBy: 'page')]
    protected Collection $urls;

    public function getUrls(): Collection
    {
        return $this->urls;
    }

    #[Groups([BasePage::GROUP_LIST, BasePage::GROUP_READ])]
    #[SerializedName('status')]
    public function getApiStatus(): string
    {
        return $this->getStatus();
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    #[Groups([BasePage::GROUP_READ])]
    #[SerializedName('content')]
    public function getApiContent(): ?array
    {
        $translation = $this->getCurrentTranslation();

        return $translation instanceof PageTranslation ? $translation->getContent() : null;
    }

    /**
     * The visual of the translation, else the page one (file id).
     */
    #[Groups([BasePage::GROUP_READ])]
    #[SerializedName('main_visual')]
    public function getApiMainVisual(): ?string
    {
        $translation = $this->getCurrentTranslation();
        $file = ($translation instanceof PageTranslation ? $translation->getMainVisual() : null) ?? $this->getMainVisual();

        return $file?->getId();
    }

    #[Groups([BasePage::GROUP_LIST, BasePage::GROUP_READ])]
    #[SerializedName('thumbnail')]
    public function getApiThumbnail(): ?string
    {
        $translation = $this->getCurrentTranslation();
        $file = ($translation instanceof PageTranslation ? $translation->getThumbnail() : null) ?? $this->getThumbnail();

        return $file?->getId();
    }

    /**
     * The URL path of each language: the stored ones of a published page (always resolved to it,
     * even while a category change waits on a conflict), else computed (preview).
     *
     * @return array<string, string> path by language iso
     */
    #[Groups([BasePage::GROUP_LIST, BasePage::GROUP_READ])]
    #[SerializedName('switch_lang')]
    public function getSwitchLang(): array
    {
        $paths = [];

        if ($this->isPublished() && !$this->urls->isEmpty()) {
            foreach ($this->urls as $url) {
                $paths[(string) $url->getLanguage()->getIso()] = $url->getPath();
            }

            return $paths;
        }

        foreach ($this->getTranslations() as $translation) {
            if ($translation instanceof PageTranslation && $translation->getLanguage() instanceof LanguageInterface && '' !== (string) $translation->getTitle()) {
                $paths[(string) $translation->getLanguage()->getIso()] = $this->getFullPath($translation->getLanguage());
            }
        }

        return $paths;
    }
}
