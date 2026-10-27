<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Cache;

use Doctrine\ORM\Event\OnFlushEventArgs;
use Gingerminds\CoreBundle\Cache\CacheKeyBuilder;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\MediaManagerBundle\Entity\File\FileInterface;
use Gingerminds\MediaManagerBundle\Entity\Media\MediaInterface;
use Symfony\Component\Cache\Adapter\TagAwareAdapterInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * The API responses of the pages hold the files and medias of their blocks (resolved content): a
 * change of a file or a media invalidates every page response, once per flush (a new one is not
 * referenced yet).
 */
final class PageReferenceCacheListener implements ResetInterface
{
    private bool $invalidate = false;

    /**
     * @param class-string<CacheableResourceInterface> $pageClass
     */
    public function __construct(
        private readonly TagAwareAdapterInterface $cache,
        private readonly CacheKeyBuilder $keyBuilder,
        private readonly string $pageClass,
        private readonly bool $enabled,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        if (!$this->enabled || $this->invalidate) {
            return;
        }

        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ([...$uow->getScheduledEntityUpdates(), ...$uow->getScheduledEntityDeletions()] as $entity) {
            if ($entity instanceof FileInterface || $entity instanceof MediaInterface) {
                $this->invalidate = true;

                return;
            }
        }
    }

    public function postFlush(): void
    {
        if (!$this->invalidate) {
            return;
        }

        $this->invalidate = false;
        $this->cache->invalidateTags([$this->keyBuilder->resourceTag($this->pageClass::getCacheKey())]);
    }

    public function reset(): void
    {
        $this->invalidate = false;
    }
}
