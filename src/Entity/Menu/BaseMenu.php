<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A menu always belongs to a site (`site_id` NOT NULL, as Laravel), the current one on creation.
 */
#[ORM\MappedSuperclass]
abstract class BaseMenu implements MenuInterface, SortableInterface, SearchableInterface, EagerLoadableInterface, CacheableResourceInterface
{
    use TimestampableTrait;

    public const string GROUP_LIST = 'menu:list';
    public const string GROUP_READ = 'menu:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'gingerminds_cms.menu.no_site')]
    protected ?SiteInterface $site = null;

    /**
     * @var Collection<int, MenuItemInterface>
     */
    #[ORM\OneToMany(targetEntity: MenuItemInterface::class, mappedBy: 'menu')]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    protected Collection $items;

    public function __construct()
    {
        $this->items = new ArrayCollection();
    }

    /**
     * Ordered by position then id, as the stored order.
     *
     * @param array<MenuItemInterface> $items
     *
     * @return list<MenuItemInterface>
     */
    public static function sortItems(array $items): array
    {
        usort($items, static fn (MenuItemInterface $a, MenuItemInterface $b): int => [$a->getPosition(), $a->getId()] <=> [$b->getPosition(), $b->getId()]);

        return $items;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;
    }

    public function getItems(): Collection
    {
        return $this->items;
    }

    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    #[SerializedName('active_items')]
    public function getActiveItems(): array
    {
        return self::sortItems(array_filter(
            $this->items->toArray(),
            static fn (MenuItemInterface $item): bool => $item->isActive() && !$item->getParent() instanceof MenuItemInterface,
        ));
    }

    public static function getEagerLoads(): array
    {
        return ['items', 'items.translations', 'items.translations.language'];
    }

    public static function getSearchableFields(): array
    {
        return ['code'];
    }

    public static function getCacheKey(): string
    {
        return 'menu';
    }

    // Barely changes: 24h instead of the default TTL.
    public static function getCacheTtl(): ?int
    {
        return 86400;
    }

    public function __toString(): string
    {
        return (string) $this->code;
    }
}
