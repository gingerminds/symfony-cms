<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * A content in one language in the `doctrine` search engine (`search_index`, as Laravel). Written by
 * DoctrineSearchEngine only (DBAL): the mapping holds the schema.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_index')]
#[ORM\UniqueConstraint(columns: ['type', 'record_id', 'language_id'])]
#[ORM\Index(columns: ['site_id', 'language_id', 'type'])]
class SearchIndex
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['unsigned' => true])]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $excerpt;

    #[ORM\Column(name: 'published_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishedAt;

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'updated_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $updatedAt;

    public function __construct(#[ORM\Column(length: 255)]
        private string $type, #[ORM\Column(name: 'record_id', type: Types::BIGINT, options: ['unsigned' => true])]
        private int $recordId, #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
        #[ORM\JoinColumn(name: 'site_id', nullable: true, onDelete: 'SET NULL')]
        private ?SiteInterface $site, #[ORM\ManyToOne(targetEntity: LanguageInterface::class)]
        #[ORM\JoinColumn(name: 'language_id', nullable: false, onDelete: 'CASCADE')]
        private LanguageInterface $language, #[ORM\Column(length: 255)]
        private string $title, #[ORM\Column(length: 255)]
        private string $url, #[ORM\Column(name: 'searchable_text', type: Types::TEXT)]
        private string $searchableText)
    {
        $this->excerpt = null;
        $this->publishedAt = null;
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = $this->createdAt;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getRecordId(): int
    {
        return $this->recordId;
    }

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    public function getLanguage(): LanguageInterface
    {
        return $this->language;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getExcerpt(): ?string
    {
        return $this->excerpt;
    }

    public function getSearchableText(): string
    {
        return $this->searchableText;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
