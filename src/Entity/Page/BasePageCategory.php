<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A category of pages (`page_categories`, as Laravel, plus `position`): a tree per site whose
 * prefixes build the page URLs.
 */
#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['code', 'site'], errorPath: 'code')]
abstract class BasePageCategory implements PageCategoryInterface, EagerLoadableInterface, CacheableResourceInterface, CacheCascadeInterface
{
    use PageCategoryTranslatedFieldsTrait;
    use PageCategoryTreeTrait;
    use TimestampableTrait;
    use TranslatableTrait;

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
    #[Assert\NotNull(message: 'gingerminds_cms.page_category.no_site')]
    protected ?SiteInterface $site = null;

    #[ORM\Column(name: 'is_unique', options: ['default' => false])]
    protected bool $unique = false;

    #[ORM\Column(name: 'is_hidden_from_search', options: ['default' => false])]
    protected bool $hiddenFromSearch = false;

    #[ORM\Column(options: ['default' => 0])]
    protected int $position = 0;

    public function __construct()
    {
        $this->children = new ArrayCollection();
        $this->translations = new ArrayCollection();
    }

    public static function getTranslationEntityClass(): string
    {
        return PageCategoryTranslation::class;
    }

    public static function getEagerLoads(): array
    {
        return [...self::getTranslationEagerLoads()];
    }

    public static function getCacheKey(): string
    {
        return 'page_category';
    }

    public static function getCacheTtl(): ?int
    {
        return null;
    }

    // The prefixes build the page paths.
    public static function getCascadeCacheKeys(): array
    {
        return ['page'];
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

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    public function isUnique(): bool
    {
        return $this->unique;
    }

    public function setUnique(bool $unique): void
    {
        $this->unique = $unique;
    }

    public function isHiddenFromSearch(): bool
    {
        return $this->hiddenFromSearch;
    }

    public function setHiddenFromSearch(bool $hiddenFromSearch): void
    {
        $this->hiddenFromSearch = $hiddenFromSearch;
    }

    public function __toString(): string
    {
        return $this->getName() ?? (string) $this->code;
    }
}
