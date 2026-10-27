<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Command;

use Gingerminds\CmsBundle\Search\SearchIndexer;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Rebuilds the search index (Laravel `search:reindex`): after the installation, or when the index
 * drifted from the contents. `--type` (repeatable) limits it to some types.
 */
#[AsCommand(name: 'gingerminds:cms:search:reindex', description: 'Rebuild the search index of every (or of some) searchable type')]
final class ReindexSearchCommand extends Command
{
    public function __construct(
        private readonly SearchResourceRegistry $resources,
        private readonly SearchIndexer $indexer,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('type', null, InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Only these types');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $registered = array_keys($this->resources->all());
        /** @var list<string> $types */
        $types = $input->getOption('type');

        foreach (array_diff($types, $registered) as $unknown) {
            $io->warning(\sprintf('Unknown search type "%s", skipped. Types: %s.', $unknown, implode(', ', $registered)));
        }

        foreach ([] === $types ? $registered : array_intersect($registered, $types) as $type) {
            $resource = $this->resources->get($type);

            if ($resource instanceof SearchResource) {
                $io->text(\sprintf('"%s": %d indexed.', $type, $this->indexer->reindex($resource, true)));
            }
        }

        $io->success('Search index rebuilt.');

        return Command::SUCCESS;
    }
}
