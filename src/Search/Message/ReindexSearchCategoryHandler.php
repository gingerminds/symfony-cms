<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Message;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchIndexer;

/**
 * Reindexes the contents of a category and of its subcategories (`getChildren()`), for every type
 * whose `category` association targets it.
 */
final readonly class ReindexSearchCategoryHandler
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private SearchIndexer $indexer,
    ) {
    }

    public function __invoke(ReindexSearchCategory $message): void
    {
        $category = $this->entityManager->find($message->categoryClass, $message->categoryId);

        if (null === $category) {
            return;
        }

        $categoryIds = $this->ids($category);

        foreach ($this->indexer->resourcesForCategory($category) as $resource) {
            $this->indexer->reindexCategories($resource, $categoryIds);
        }
    }

    /**
     * @return list<int> the category and its descendants
     */
    private function ids(object $category): array
    {
        $ids = [];
        $id = SearchDocumentFactory::id($category);

        if (null !== $id) {
            $ids[] = $id;
        }

        $children = method_exists($category, 'getChildren') ? $category->getChildren() : [];

        foreach (is_iterable($children) ? $children : [] as $child) {
            if (\is_object($child)) {
                $ids = [...$ids, ...$this->ids($child)];
            }
        }

        return $ids;
    }
}
