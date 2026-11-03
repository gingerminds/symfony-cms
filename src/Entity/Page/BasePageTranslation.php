<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\TranslationSiteTrait;
use Gingerminds\CmsBundle\Model\VisualsTrait;
use Gingerminds\CmsBundle\Validator\UniquePageUrl;
use Gingerminds\CmsBundle\Validator\UniqueSlugInCategory;
use Gingerminds\CoreBundle\Model\CacheCascade;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A page in one language: title, slug (unique in the category, blank: the category home page),
 * hook, content blocks, visuals overriding the page ones. `site_id` copies the one of the page.
 * The owner relation (`page_id`) is mapped by multisite, on the overridable PageInterface.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
#[UniqueSlugInCategory]
#[UniquePageUrl]
#[CacheCascade(['page'])]
abstract class BasePageTranslation implements PageTranslationInterface
{
    use TimestampableTrait;
    use TranslationSiteTrait;
    use TranslationTrait;
    use VisualsTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: true, onDelete: 'CASCADE')]
    protected ?SiteInterface $site = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $title = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: PageCategoryTranslation::PREFIX_PATTERN, message: 'gingerminds_cms.page.slug_format')]
    protected ?string $slug = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    protected ?string $hook = null;

    /**
     * @var list<array<string, mixed>>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    protected ?array $content = null;

    public static function getTranslatableEntityClass(): string
    {
        return PageInterface::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    /**
     * A blank slug is stored as null (the page is the home page of its category).
     */
    public function setSlug(?string $slug): void
    {
        $slug = null !== $slug ? trim($slug) : null;
        $this->slug = '' === $slug ? null : $slug;
    }

    public function getHook(): ?string
    {
        return $this->hook;
    }

    public function setHook(?string $hook): void
    {
        $this->hook = $hook;
    }

    /**
     * @return list<array<string, mixed>>|null
     */
    public function getContent(): ?array
    {
        return $this->content;
    }

    /**
     * @param list<array<string, mixed>>|null $content
     */
    public function setContent(?array $content): void
    {
        $this->content = $content;
    }
}
