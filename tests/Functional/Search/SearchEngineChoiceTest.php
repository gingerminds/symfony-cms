<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Search;

use Gingerminds\CmsBundle\Search\Doctrine\Entity\SearchIndex;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Tests\Application\Search\InMemorySearchEngine;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * `gingerminds_cms.search.engine`: a project engine is kept in sync and read by the API; the tables
 * of the doctrine engine are not mapped.
 */
final class SearchEngineChoiceTest extends ApiTestCase
{
    public function testAProjectEngineReplacesTheDoctrineOne(): void
    {
        $engine = self::getContainer()->get('test.memory_search_engine');
        self::assertInstanceOf(InMemorySearchEngine::class, $engine);
        self::assertSame($engine, self::getContainer()->get(SearchEngineInterface::class));
        self::assertNotContains(SearchIndex::class, $this->entityManager()->getConfiguration()->getMetadataDriverImpl()?->getAllClassNames() ?? [], 'The search_index table is not mapped.');

        $fr = $this->fixtures->language('fr');
        $site = $this->fixtures->site('main', [$fr], $fr);
        $page = $this->fixtures->page($site, 'tractor', ['fr' => ['Tracteur rouge', 'tracteur-rouge']], status: 'published');

        $documents = $engine->documents['page:' . $page->getId()] ?? [];
        self::assertCount(1, $documents);
        self::assertSame(['title' => 'Tracteur rouge', 'hook' => ''], $documents[0]->fields);
        self::assertSame(['title' => 3, 'hook' => 1], $documents[0]->weights);

        self::assertSame(['Tracteur rouge'], array_column($this->api('GET', '/api/search-indices?filters[search]=rouge', locale: 'fr')['member'], 'title'));

        $page->setStatus('draft');
        $this->entityManager()->flush();
        self::assertSame([], $engine->documents);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_memory_search', ...$options]);
    }
}
