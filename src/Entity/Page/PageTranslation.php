<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\TranslationFieldsTrait;
use Gingerminds\CmsBundle\Model\TranslationSiteTrait;
use Gingerminds\CmsBundle\Model\VisualsInterface;
use Gingerminds\CmsBundle\Model\VisualsTrait;
use Gingerminds\CmsBundle\Validator\UniquePageUrl;
use Gingerminds\CmsBundle\Validator\UniqueSlugInCategory;
use Gingerminds\CoreBundle\Model\CacheCascadeInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableInterface;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A page in one language: title, slug (unique in the category, blank: the category home page),
 * hook, content blocks, visuals overriding the page ones. `site_id` copies the one of the page.
 * The owner relation is declared here (see MenuItemTranslation).
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_translations')]
#[ORM\UniqueConstraint(name: 'page_translations_page_id_language_id_unique', columns: ['page_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
#[UniqueSlugInCategory]
#[UniquePageUrl]
class PageTranslation implements TranslationInterface, VisualsInterface, TimestampableInterface, CacheCascadeInterface
{
    use TimestampableTrait;
    use TranslationFieldsTrait;
    use TranslationSiteTrait;
    use VisualsTrait;

    #[ORM\ManyToOne(targetEntity: PageInterface::class, inversedBy: 'translations')]
    #[ORM\JoinColumn(name: 'page_id', nullable: false, onDelete: 'CASCADE')]
    protected ?PageInterface $translatable = null;

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
        return Page::class;
    }

    public static function getCascadeCacheKeys(): array
    {
        return ['page'];
    }

    public function getTranslatable(): ?TranslatableInterface
    {
        return $this->translatable;
    }

    public function setTranslatable(?TranslatableInterface $translatable): void
    {
        if ($translatable instanceof TranslatableInterface && !$translatable instanceof PageInterface) {
            throw new \InvalidArgumentException(\sprintf('A page translation belongs to a "%s".', PageInterface::class));
        }

        $this->translatable = $translatable;
        $this->site = $translatable?->getSite() ?? $this->site;
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
