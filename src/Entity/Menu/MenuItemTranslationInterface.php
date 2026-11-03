<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;

/**
 * A menu item in one language (`resources.menu_item_translation.entity`, resolve_target_entities).
 */
interface MenuItemTranslationInterface extends TranslationInterface, TimestampableInterface
{
    public function getId(): ?int;

    public function getName(): ?string;

    public function setName(?string $name): void;

    public function getUrl(): ?string;

    public function setUrl(?string $url): void;

    public function getDescription(): ?string;

    public function setDescription(?string $description): void;
}
