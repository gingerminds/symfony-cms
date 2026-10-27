<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Workflow;

use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\PublishableInterface;
use Symfony\Component\Workflow\Event\EnteredEvent;

/**
 * Dates of the `gingerminds_cms_publication` workflow, as the Laravel transitions: entering `published`
 * sets `published_at`, `archived` sets `archived_at` and clears `published_at`, `draft` clears both.
 * The other places of a project leave them as they are.
 */
final class PublicationDatesListener
{
    /**
     * @param EnteredEvent<object> $event
     */
    public function __invoke(EnteredEvent $event): void
    {
        $subject = $event->getSubject();

        if (!$subject instanceof PublishableInterface) {
            return;
        }

        $now = new \DateTimeImmutable();

        match ($subject->getStatus()) {
            PublicationStatus::PUBLISHED => $subject->setPublishedAt($now),
            PublicationStatus::ARCHIVED => $this->archive($subject, $now),
            PublicationStatus::DRAFT => $this->clear($subject),
            default => null,
        };
    }

    private function archive(PublishableInterface $subject, \DateTimeImmutable $now): void
    {
        $subject->setArchivedAt($now);
        $subject->setPublishedAt(null);
    }

    private function clear(PublishableInterface $subject): void
    {
        $subject->setPublishedAt(null);
        $subject->setArchivedAt(null);
    }
}
