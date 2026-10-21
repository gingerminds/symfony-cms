<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\Collection;
use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;

interface PageCategoryInterface extends ResourceInterface, SiteContextedInterface, TranslatableInterface, TimestampableInterface, \Stringable
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;

    public function getParent(): ?self;

    public function setParent(?self $parent): void;

    /**
     * @return Collection<int, self>
     */
    public function getChildren(): Collection;

    public function getPosition(): int;

    public function setPosition(int $position): void;

    /**
     * Only one page can be attached to the category.
     */
    public function isUnique(): bool;

    public function setUnique(bool $unique): void;

    public function isHiddenFromSearch(): bool;

    public function setHiddenFromSearch(bool $hiddenFromSearch): void;

    public function getName(): ?string;

    /**
     * The prefix in `$language` (null: the current language), without fallback.
     */
    public function getPrefix(LanguageInterface|int|null $language = null): ?string;

    /**
     * The prefixes of the category and of its ancestors, root first, joined by `/` (empty
     * prefixes skipped), in `$language` (null: the current language).
     */
    public function getFullPath(LanguageInterface|int|null $language = null): string;
}
