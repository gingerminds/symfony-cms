<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;

/**
 * A page category in one language (`resources.page_category_translation.entity`,
 * resolve_target_entities).
 */
interface PageCategoryTranslationInterface extends TranslationInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getSite(): ?SiteInterface;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getPrefix(): ?string;

    public function setPrefix(?string $prefix): void;
}
