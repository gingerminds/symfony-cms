<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\CmsBundle\Model\PublishableInterface;
use Gingerminds\CmsBundle\Model\VisualsInterface;
use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;

interface PageInterface extends ResourceInterface, SiteContextedInterface, TranslatableInterface, PublishableInterface, VisualsInterface, TimestampableInterface, \Stringable
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;

    public function getCategory(): ?PageCategoryInterface;

    public function setCategory(?PageCategoryInterface $category): void;

    public function isHiddenFromSearch(): bool;

    public function setHiddenFromSearch(bool $hiddenFromSearch): void;

    public function getTitle(): ?string;

    public function getSlug(): ?string;

    public function getHook(): ?string;

    /**
     * The category path and the slug in `$language` (null: the current language), joined by `/`
     * (blank parts skipped): the page URL.
     */
    public function getFullPath(LanguageInterface|int|null $language = null): string;
}
