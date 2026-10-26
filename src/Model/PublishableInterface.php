<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

/**
 * An entity whose `status` is driven by the `gingerminds_cms_publication` workflow (PublishableTrait):
 * the pages, and the project entities added to the `supports` of the workflow.
 */
interface PublishableInterface
{
    public function getStatus(): string;

    public function setStatus(string $status): void;

    /**
     * In the `published` place: visible on the fronts.
     */
    public function isPublished(): bool;

    public function getPublishedAt(): ?\DateTimeImmutable;

    public function setPublishedAt(?\DateTimeImmutable $publishedAt): void;

    public function getArchivedAt(): ?\DateTimeImmutable;

    public function setArchivedAt(?\DateTimeImmutable $archivedAt): void;
}
