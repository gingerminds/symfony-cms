<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\TranslationFieldsTrait;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The owner relation is declared here: on an overridable item (MenuItemInterface target),
 * the multisite convention would name its column after the interface.
 */
#[ORM\Entity]
#[ORM\Table(name: 'menu_item_translations')]
#[ORM\UniqueConstraint(name: 'menu_item_translations_menu_item_id_language_id_unique', columns: ['menu_item_id', 'language_id'])]
class MenuItemTranslation implements TranslationInterface, TimestampableInterface, CacheCascadeInterface
{
    use TimestampableTrait;
    use TranslationFieldsTrait;

    #[ORM\ManyToOne(targetEntity: MenuItemInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'menu_item_id', nullable: false, onDelete: 'CASCADE')]
    protected ?MenuItemInterface $translatable = null;

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
        return MenuItem::class;
    }

    // The menu cache embeds the item tree.
    public static function getCascadeCacheKeys(): array
    {
        return ['menu'];
    }

    public function getTranslatable(): ?TranslatableInterface
    {
        return $this->translatable;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        if ($translatable instanceof TranslatableInterface && !$translatable instanceof MenuItemInterface) {
            throw new \InvalidArgumentException(\sprintf('A menu item translation belongs to a "%s".', MenuItemInterface::class));
        }

        $this->translatable = $translatable;
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
