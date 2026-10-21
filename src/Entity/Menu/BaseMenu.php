<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A menu always belongs to a site (`site_id` NOT NULL, as Laravel), the current one on creation.
 */
#[ORM\MappedSuperclass]
abstract class BaseMenu implements MenuInterface, SortableInterface, SearchableInterface, CacheableResourceInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'gingerminds_cms.menu.no_site')]
    protected ?SiteInterface $site = null;

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
