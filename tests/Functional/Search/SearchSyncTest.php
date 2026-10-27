<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Search;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * The search index (doctrine engine) follows the pages: publication, translations, hidden flags of
 * the page and of its category branch, URLs, deletion; the reindex command rebuilds it.
 */
final class SearchSyncTest extends ApiTestCase
{
    private Site $site;
    private PageCategory $products;
    private PageCategory $tractors;

    protected function setUp(): void
    {
        parent::setUp();
        $fr = $this->fixtures->language('fr');
        $this->site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
        $this->products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $this->tractors = $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $this->products);
    }

    public function testAPublishedPageIsIndexedInEachOfItsLanguages(): void
    {
        $page = $this->fixtures->page($this->site, 'x', ['fr' => ['Tracteur X', 'tracteur-x'], 'en' => ['Tractor X', 'tractor-x']], $this->tractors, 'published');
        $this->fixtures->page($this->site, 'draft', ['fr' => ['Brouillon', 'brouillon']]);
        $this->translation($page, 'fr')->setHook('<p>Le <strong>meilleur</strong> tracteur.</p>');
        $this->entityManager()->flush();

        self::assertSame([
            ['page', $page->getId(), 'fr', 'Tracteur X', 'produits/tracteurs/tracteur-x', '<p>Le <strong>meilleur</strong> tracteur.</p>', 'Tracteur X Le meilleur tracteur.', $this->site->getId()],
            ['page', $page->getId(), 'en', 'Tractor X', 'tractor-x', null, 'Tractor X', $this->site->getId()],
        ], $this->rows());
        self::assertSame([[$page->getId(), $this->tractors->getId()], [$page->getId(), $this->tractors->getId()]], $this->categoryRows());

        // Unpublished: removed.
        $page->setStatus('draft');
        $this->entityManager()->flush();
        self::assertSame([], $this->rows());
    }

    public function testAChangeOfATranslationOrOfAPrefixUpdatesTheIndex(): void
    {
        $page = $this->fixtures->page($this->site, 'x', ['fr' => ['Tracteur X', 'tracteur-x']], $this->tractors, 'published');

        $this->translation($page, 'fr')->setTitle('Tracteur XL');
        $this->entityManager()->flush();
        self::assertSame('Tracteur XL', $this->rows()[0][3] ?? null);

        // The URL of a page follows the prefixes of its categories (page_urls).
        $translation = $this->tractors->getTranslations()->first();
        self::assertInstanceOf(PageCategoryTranslation::class, $translation);
        $translation->setPrefix('engins');
        $this->entityManager()->flush();
        self::assertSame('produits/engins/tracteur-x', $this->rows()[0][4] ?? null);
    }

    public function testAHiddenPageOrCategoryBranchIsRemoved(): void
    {
        $page = $this->fixtures->page($this->site, 'x', ['fr' => ['Tracteur X', 'tracteur-x']], $this->tractors, 'published');
        $other = $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');

        $page->setHiddenFromSearch(true);
        $this->entityManager()->flush();
        self::assertSame(['about'], $this->indexedCodes());

        $page->setHiddenFromSearch(false);
        $this->entityManager()->flush();
        self::assertSame(['about', 'x'], $this->indexedCodes());

        // Hiding an ancestor category hides the pages of its subcategories.
        $this->products->setHiddenFromSearch(true);
        $this->entityManager()->flush();
        self::assertSame(['about'], $this->indexedCodes());

        $this->products->setHiddenFromSearch(false);
        $this->entityManager()->flush();
        self::assertSame(['about', 'x'], $this->indexedCodes());

        $this->entityManager()->remove($other);
        $this->entityManager()->flush();
        self::assertSame(['x'], $this->indexedCodes());
    }

    public function testTheReindexCommandRebuildsTheIndex(): void
    {
        $this->fixtures->page($this->site, 'x', ['fr' => ['Tracteur X', 'tracteur-x']], $this->tractors, 'published');
        $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $this->fixtures->page($this->site, 'draft', ['fr' => ['Brouillon', 'brouillon']]);
        $this->entityManager()->getConnection()->executeStatement('DELETE FROM search_index_categories');
        $this->entityManager()->getConnection()->executeStatement('DELETE FROM search_index');

        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('gingerminds:cms:search:reindex'));
        $tester->execute(['--type' => ['page', 'news']]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('Unknown search type "news"', $tester->getDisplay());
        self::assertStringContainsString('"page": 2 indexed.', $tester->getDisplay(), 'Not the draft.');
        self::assertSame(['about', 'x'], $this->indexedCodes());
    }

    private function translation(Page $page, string $iso): PageTranslation
    {
        foreach ($page->getTranslations() as $translation) {
            if ($translation instanceof PageTranslation && $iso === $translation->getLanguage()?->getIso()) {
                return $translation;
            }
        }

        self::fail('No ' . $iso . ' translation.');
    }

    /**
     * @return list<array{string, int, string, string, string, ?string, string, ?int}>
     */
    private function rows(): array
    {
        $rows = $this->entityManager()->getConnection()->fetchAllAssociative(
            'SELECT s.type, s.record_id, l.iso, s.title, s.url, s.excerpt, s.searchable_text, s.site_id FROM search_index s JOIN languages l ON l.id = s.language_id ORDER BY s.record_id, l.iso DESC',
        );

        return array_map(static fn (array $row): array => [
            (string) $row['type'], (int) $row['record_id'], (string) $row['iso'], (string) $row['title'], (string) $row['url'],
            null === $row['excerpt'] ? null : (string) $row['excerpt'], (string) $row['searchable_text'], null === $row['site_id'] ? null : (int) $row['site_id'],
        ], $rows);
    }

    /**
     * @return list<array{int, int}>
     */
    private function categoryRows(): array
    {
        $rows = $this->entityManager()->getConnection()->fetchAllAssociative(
            'SELECT s.record_id, c.category_id FROM search_index_categories c JOIN search_index s ON s.id = c.search_index_id ORDER BY s.id',
        );

        return array_map(static fn (array $row): array => [(int) $row['record_id'], (int) $row['category_id']], $rows);
    }

    /**
     * @return list<string>
     */
    private function indexedCodes(): array
    {
        /** @var list<string> */
        return $this->entityManager()->getConnection()->fetchFirstColumn(
            'SELECT DISTINCT p.code FROM search_index s JOIN pages p ON p.id = s.record_id ORDER BY p.code',
        );
    }
}
