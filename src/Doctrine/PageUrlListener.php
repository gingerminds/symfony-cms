<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Doctrine;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslationInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslationInterface;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

/**
 * Syncs the `page_urls` in the flush changing a page, a page translation, a category or a category
 * translation (the pages of its subtree), whatever made the change (admin, command...). Runs before
 * the other onFlush listeners (priority), which see the URL changes.
 */
final readonly class PageUrlListener
{
    public const int PRIORITY = 100;

    public function __construct(
        private PageUrlSyncer $syncer,
        private PageRepository $pageRepository,
        private PageCategoryRepository $categoryRepository,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $entityManager = $args->getObjectManager();
        $uow = $entityManager->getUnitOfWork();
        $pages = [];
        $categoryIds = [];

        // A deleted category: its pages lose it beforehand (PageCategoryRepository), as updates.
        $deletedTranslations = array_filter($uow->getScheduledEntityDeletions(), static fn (object $entity): bool => $entity instanceof PageTranslationInterface);

        foreach ([...$uow->getScheduledEntityInsertions(), ...$uow->getScheduledEntityUpdates(), ...$deletedTranslations] as $entity) {
            $page = match (true) {
                $entity instanceof PageInterface => $entity,
                $entity instanceof PageTranslationInterface => $entity->getTranslatable(),
                default => null,
            };

            if ($page instanceof PageInterface) {
                $pages[spl_object_id($page)] = $page;
            }

            $category = match (true) {
                $entity instanceof PageCategoryInterface => $entity,
                $entity instanceof PageCategoryTranslationInterface => $entity->getTranslatable(),
                default => null,
            };

            if ($category instanceof PageCategoryInterface && null !== $category->getId()) {
                $categoryIds[$category->getId()] = $category->getId();
            }
        }

        if ([] !== $categoryIds) {
            foreach ($this->pageRepository->findInCategories($this->categoryRepository->findIdsWithDescendants(array_values($categoryIds))) as $page) {
                $pages[spl_object_id($page)] = $page;
            }
        }

        $pages = array_filter($pages, static fn (PageInterface $page): bool => !$uow->isScheduledForDelete($page));

        if ([] === $pages) {
            return;
        }

        $metadata = $entityManager->getClassMetadata(PageUrl::class);

        foreach ($this->syncer->sync(...array_values($pages)) as $url) {
            if (null === $url->getId()) {
                $uow->computeChangeSet($metadata, $url);
            } else {
                $uow->recomputeSingleEntityChangeSet($metadata, $url);
            }
        }
    }
}
