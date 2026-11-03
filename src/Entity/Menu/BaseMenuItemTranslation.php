<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheCascade;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Name, URL and description of a menu item in one language. The owner relation (`menu_item_id`)
 * is mapped by multisite, on the overridable MenuItemInterface.
 */
#[ORM\MappedSuperclass]
// The menu cache embeds the item tree.
#[CacheCascade(['menu'])]
abstract class BaseMenuItemTranslation implements MenuItemTranslationInterface
{
    use TimestampableTrait;
    use TranslationTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    protected ?string $url = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $description = null;

    public static function getTranslatableEntityClass(): string
    {
        return MenuItemInterface::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): void
    {
        $this->url = $url;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
