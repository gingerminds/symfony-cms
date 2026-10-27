<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Gingerminds\CmsBundle\Search\ExcludableFromSearchInterface;
use Gingerminds\CmsBundle\Search\Message\ReindexSearchCategory;
use Gingerminds\CmsBundle\Search\PubliclySearchableInterface;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchIndexer;
use Gingerminds\CmsBundle\Search\SearchOwnedInterface;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Keeps the search in sync after a flush: a searchable content changed (itself, a translation, a
 * SearchOwnedInterface entity such as its URLs) is reindexed, a deleted one removed; a category
 * whose `hiddenFromSearch` or `parent` changed reindexes its contents (ReindexSearchCategory).
 */
final class SearchIndexListener implements ResetInterface
{
    private const array CATEGORY_FIELDS = ['hiddenFromSearch', 'parent'];

    /** @var array<int, PubliclySearchableInterface> */
    private array $contents = [];

    /** @var array<string, array{string, int}> type and id of the deleted contents */
    private array $removed = [];

    /** @var array<string, ReindexSearchCategory> */
    private array $categories = [];

    public function __construct(
        private readonly SearchResourceRegistry $resources,
        private readonly SearchIndexer $indexer,
        private readonly MessageBusInterface $bus,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates()] as $entity) {
            $this->collect($entity);
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            $changed = array_intersect(self::CATEGORY_FIELDS, array_keys($uow->getEntityChangeSet($entity)));

            if ($entity instanceof ExcludableFromSearchInterface && !$entity instanceof PubliclySearchableInterface && [] !== $changed) {
                $this->collectCategory($entity, $args->getObjectManager()->getClassMetadata($entity::class)->getName());
            }
        }

        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            $this->collectDeletion($entity);
        }
    }

    public function postFlush(): void
    {
        [$contents, $removed, $categories] = [$this->contents, $this->removed, $this->categories];
        $this->reset();

        foreach ($removed as [$type, $id]) {
            $this->indexer->remove($type, $id);
        }

        foreach ($contents as $content) {
            $resource = $this->resources->forEntity($content);

            // Deleted in the same flush (its translations were reported first).
            if ($resource instanceof SearchResource && !isset($removed[$resource->type . ':' . SearchDocumentFactory::id($content)])) {
                $this->indexer->index($content);
            }
        }

        foreach ($categories as $message) {
            $this->bus->dispatch($message);
        }
    }

    public function reset(): void
    {
        $this->contents = [];
        $this->removed = [];
        $this->categories = [];
    }

    private function collect(object $entity): void
    {
        $content = match (true) {
            $entity instanceof PubliclySearchableInterface => $entity,
            $entity instanceof TranslationInterface => $entity->getTranslatable(),
            $entity instanceof SearchOwnedInterface => $entity->getSearchOwner(),
            default => null,
        };

        if ($content instanceof PubliclySearchableInterface && $this->resources->forEntity($content) instanceof SearchResource) {
            $this->contents[spl_object_id($content)] = $content;
        }
    }

    private function collectDeletion(object $entity): void
    {
        if (!$entity instanceof PubliclySearchableInterface) {
            // A deleted translation or URL: its content is reindexed (unless deleted too).
            $this->collect($entity);

            return;
        }

        $resource = $this->resources->forEntity($entity);
        $id = SearchDocumentFactory::id($entity);
        unset($this->contents[spl_object_id($entity)]);

        if ($resource instanceof SearchResource && null !== $id) {
            $this->removed[$resource->type . ':' . $id] = [$resource->type, $id];
        }
    }

    /**
     * @param class-string $class the entity class (not a proxy one)
     */
    private function collectCategory(object $category, string $class): void
    {
        $id = SearchDocumentFactory::id($category);

        if (null !== $id && [] !== $this->indexer->resourcesForCategory($category)) {
            $this->categories[$class . ':' . $id] = new ReindexSearchCategory($class, $id);
        }
    }
}
