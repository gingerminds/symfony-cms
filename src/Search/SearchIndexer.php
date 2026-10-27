<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Keeps the search engine in sync: a visible content is (re)indexed in every language it has, a
 * hidden one (unpublished, excluded, or excluded by a category) is removed.
 */
class SearchIndexer
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly SearchResourceRegistry $resources,
        private readonly SearchDocumentFactory $documents,
        private readonly SearchEngineInterface $engine,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return bool whether the content is in the index (else removed from it)
     */
    public function index(PubliclySearchableInterface $entity): bool
    {
        $resource = $this->resources->forEntity($entity);
        $recordId = SearchDocumentFactory::id($entity);

        if (!$resource instanceof SearchResource || null === $recordId) {
            return false;
        }

        $documents = self::isVisible($entity) ? $this->documents->documents($resource, $entity) : [];

        if ([] === $documents) {
            $this->engine->remove($resource->type, $recordId);

            return false;
        }

        $this->engine->index($resource->type, $recordId, $documents);

        return true;
    }

    public function remove(string $type, int $recordId): void
    {
        $this->engine->remove($type, $recordId);
    }

    /**
     * Rebuilds the index of a type, by batches.
     *
     * @param bool $detach clears the entity manager after each batch (commands)
     *
     * @return int the indexed contents (the hidden ones are not)
     */
    public function reindex(SearchResource $resource, bool $detach = false): int
    {
        $this->engine->clear($resource->type);

        return $this->indexBatches($resource, [], $detach);
    }

    /**
     * Reindexes the contents of categories (a category hidden from search, or shown again).
     *
     * @param list<int> $categoryIds
     */
    public function reindexCategories(SearchResource $resource, array $categoryIds): int
    {
        return null === $resource->category || [] === $categoryIds ? 0 : $this->indexBatches($resource, $categoryIds, false);
    }

    /**
     * The types whose `category` association targets this category.
     *
     * @return list<SearchResource>
     */
    public function resourcesForCategory(object $category): array
    {
        $resources = [];

        foreach ($this->resources->all() as $resource) {
            if (null === $resource->category) {
                continue;
            }

            $metadata = $this->entityManager->getClassMetadata($resource->entity);

            if ($metadata->hasAssociation($resource->category) && is_a($category, $metadata->getAssociationTargetClass($resource->category))) {
                $resources[] = $resource;
            }
        }

        return $resources;
    }

    public static function isVisible(PubliclySearchableInterface $entity): bool
    {
        return $entity->isPubliclyVisible() && (!$entity instanceof ExcludableFromSearchInterface || !$entity->isExcludedFromSearch());
    }

    /**
     * @param list<int> $categoryIds only the contents of these categories, when not empty
     */
    private function indexBatches(SearchResource $resource, array $categoryIds, bool $detach): int
    {
        $lastId = 0;
        $count = 0;

        do {
            $qb = $this->entityManager->createQueryBuilder()
                ->select('e')
                ->from($resource->entity, 'e')
                ->where('e.id > :last')
                ->setParameter('last', $lastId)
                ->orderBy('e.id')
                ->setMaxResults(self::BATCH_SIZE);

            if ([] !== $categoryIds && null !== $resource->category) {
                $qb->distinct()->innerJoin('e.' . $resource->category, 'c')->andWhere('c.id IN (:categories)')->setParameter('categories', $categoryIds);
            }

            /** @var list<PubliclySearchableInterface> $entities */
            $entities = $qb->getQuery()->getResult();

            foreach ($entities as $entity) {
                $count += $this->index($entity) ? 1 : 0;
                $lastId = SearchDocumentFactory::id($entity) ?? $lastId;
            }

            if ($detach) {
                $this->entityManager->clear();
            }
        } while (self::BATCH_SIZE === \count($entities));

        return $count;
    }
}
