<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Recomputes the `page_urls` of every page (replaces the Laravel 0027 backfill migration), then
 * lists the paths kept because of a conflict.
 */
#[AsCommand(name: 'gingerminds:cms:page-urls:sync', description: 'Recompute the URL paths of the pages')]
final class SyncPageUrlsCommand extends Command
{
    private const int BATCH_SIZE = 100;

    public function __construct(
        private readonly PageRepository $pages,
        private readonly PageUrlSyncer $syncer,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->syncer->reset();
        $count = 0;

        foreach ($this->pages->findBy([], ['id' => 'ASC']) as $page) {
            $this->syncer->sync($page);

            if (0 === ++$count % self::BATCH_SIZE) {
                $this->entityManager->flush();
            }
        }

        $this->entityManager->flush();
        $io->success(\sprintf('%d page(s) synced.', $count));

        $conflicts = $this->syncer->getConflicts();

        if ([] !== $conflicts) {
            $io->warning(\sprintf('%d path(s) taken by another page: these pages keep their former path.', \count($conflicts)));
            $io->table(['Page', 'Language', 'Path', 'Taken by'], array_map(static fn ($conflict): array => [
                $conflict->page->getCode(),
                $conflict->language->getIso(),
                '/' . $conflict->path,
                $conflict->owner->getCode(),
            ], $conflicts));
        }

        return Command::SUCCESS;
    }
}
