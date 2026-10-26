<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Syncs the `page_urls` after a flush changing a page, a page translation, a category or a
 * category translation (the pages of its subtree), whatever made the change (admin, command...).
 */
final class PageUrlListener implements ResetInterface
{
    /**
     * @var array<int, PageInterface>
     */
    private array $pages = [];

    /**
     * @var array<int, true>
     */
    private array $categoryIds = [];

    private bool $syncing = false;

    public function __construct(
        private readonly PageUrlSyncer $syncer,
        private readonly PageRepository $pageRepository,
        private readonly PageCategoryRepository $categoryRepository,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if ($this->syncing) {
            return;
        }

        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates()] as $entity) {
            $this->collect($entity);
        }

        // A deleted category: its pages lose it beforehand (PageCategoryRepository), as updates.
        foreach ($uow->getScheduledEntityDeletions() as $entity) {
            if ($entity instanceof PageTranslation) {
                $this->collect($entity);
            }
        }
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        if ($this->syncing || ([] === $this->pages && [] === $this->categoryIds)) {
            return;
        }

        $pages = $this->pages;

        if ([] !== $this->categoryIds) {
            foreach ($this->pageRepository->findInCategories($this->categoryRepository->findIdsWithDescendants(array_keys($this->categoryIds))) as $page) {
                $pages[spl_object_id($page)] = $page;
            }
        }

        $this->reset();
        $this->syncing = true;

        try {
            foreach ($pages as $page) {
                if (null !== $page->getId()) {
                    $this->syncer->sync($page);
                }
            }

            $args->getObjectManager()->flush();
        } finally {
            $this->syncing = false;
        }
    }

    public function reset(): void
    {
        $this->pages = [];
        $this->categoryIds = [];
    }

    private function collect(object $entity): void
    {
        $page = match (true) {
            $entity instanceof PageInterface => $entity,
            $entity instanceof PageTranslation => $entity->getTranslatable(),
            default => null,
        };

        if ($page instanceof PageInterface) {
            $this->pages[spl_object_id($page)] = $page;
        }

        $category = match (true) {
            $entity instanceof PageCategoryInterface => $entity,
            $entity instanceof PageCategoryTranslation => $entity->getTranslatable(),
            default => null,
        };

        if ($category instanceof PageCategoryInterface && null !== $category->getId()) {
            $this->categoryIds[$category->getId()] = true;
        }
    }
}
