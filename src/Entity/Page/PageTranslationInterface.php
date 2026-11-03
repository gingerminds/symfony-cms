<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\CmsBundle\Model\VisualsInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;

/**
 * A page in one language (`resources.page_translation.entity`, resolve_target_entities).
 */
interface PageTranslationInterface extends TranslationInterface, VisualsInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getSite(): ?SiteInterface;

    public function getTitle(): ?string;

    public function setTitle(?string $title): void;

    public function getSlug(): ?string;

    public function setSlug(?string $slug): void;

    public function getHook(): ?string;

    public function setHook(?string $hook): void;

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getContent(): ?array;

    /**
     * @param list<array<string, mixed>>|null $content
     */
    public function setContent(?array $content): void;
}
