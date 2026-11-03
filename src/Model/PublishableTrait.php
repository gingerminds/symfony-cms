<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CoreBundle\Model\Filter;
use Gingerminds\CoreBundle\Model\Sortable;

/**
 * Implements PublishableInterface: `status` (the workflow marking), `published_at`, `archived_at`
 * (set by PublicationDatesListener).
 */
trait PublishableTrait
{
    #[ORM\Column(length: 255, options: ['default' => PublicationStatus::DRAFT])]
    #[Sortable]
    #[Filter(type: Filter::EXACT, label: 'publication.field.status', choices: PublicationStatus::LABELS, multiple: true)]
    protected string $status = PublicationStatus::DRAFT;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Sortable]
    #[Filter(label: 'publication.field.published_at')]
    protected ?\DateTimeImmutable $publishedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    protected ?\DateTimeImmutable $archivedAt = null;

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function isPublished(): bool
    {
        return PublicationStatus::PUBLISHED === $this->status;
    }

    public function getPublishedAt(): ?\DateTimeImmutable
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): void
    {
        $this->publishedAt = $publishedAt;
    }

    public function getArchivedAt(): ?\DateTimeImmutable
    {
        return $this->archivedAt;
    }

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): void
    {
        $this->archivedAt = $archivedAt;
    }
}
