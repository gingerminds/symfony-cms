<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Search;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;

/**
 * /api/search-indices and /api/search-results (doctrine engine), with `translation_fallback`.
 */
final class SearchApiTest extends ApiTestCase
{
    private Site $site;
    private PageCategory $products;
    private PageCategory $news;
    private Page $tractor;

    protected function setUp(): void
    {
        parent::setUp();
        $fr = $this->fixtures->language('fr');
        $this->site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
        $this->products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits'], 'en' => ['Products', 'products']]);
        $this->news = $this->fixtures->pageCategory($this->site, 'news', ['fr' => ['Actualités', 'actualites']]);
        $this->tractor = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur rouge', 'tracteur-rouge'], 'en' => ['Red tractor', 'red-tractor']], $this->products, 'published');
        $this->tractor->setPublishedAt(new \DateTimeImmutable('2026-03-01 10:00:00'));
        $this->entityManager()->flush();
        $this->fixtures->page($this->site, 'show', ['fr' => ['Salon du tracteur', 'salon']], $this->news, 'published');
        $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $this->fixtures->page($this->site, 'draft', ['fr' => ['Tracteur brouillon', 'brouillon']]);
        // Another site.
        $other = $this->fixtures->site('other', [$fr], $fr, 'https://other.example.com');
        $this->fixtures->page($other, 'elsewhere', ['fr' => ['Tracteur ailleurs', 'ailleurs']], status: 'published');
    }

    public function testTheLeanHits(): void
    {
        $data = $this->api('GET', '/api/search-indices?filters[search]=tracteur', locale: 'fr');

        self::assertStatus(200);
        self::assertSame(2, $data['totalItems']);
        $hits = array_column($data['member'], null, 'title');
        self::assertEqualsCanonicalizing(['Tracteur rouge', 'Salon du tracteur'], array_keys($hits));
        $hit = $hits['Tracteur rouge'];
        self::assertSame([$this->tractor->getId(), 'page', 'produits/tracteur-rouge', null], [$hit['id'], $hit['type'], $hit['url'], $hit['excerpt'] ?? null]);
        self::assertSame('2026-03-01T10:00:00+00:00', $hit['published_at']);
        self::assertArrayNotHasKey('resource', $hit);
        self::assertArrayNotHasKey('filters', $data, 'No facets on the lean hits.');

        $data = $this->api('GET', '/api/search-indices?filters[search]=tracteur&itemsPerPage=1&page=2', locale: 'fr');
        self::assertCount(1, $data['member']);
        self::assertSame(2, $data['totalItems']);
    }

    public function testTheResultsWithTheirContentAndTheFacets(): void
    {
        $data = $this->api('GET', '/api/search-results?filters[search]=tracteur', locale: 'fr');

        self::assertStatus(200);
        $hits = array_column($data['member'], null, 'title');
        self::assertSame('tractor', $hits['Tracteur rouge']['resource']['code'] ?? null, 'The page as in its list.');
        self::assertSame('Tracteur rouge', $hits['Tracteur rouge']['resource']['title'] ?? null);
        self::assertSame([
            ['value' => 'page', 'label' => 'Pages', 'total' => 2, 'group' => null],
            ['value' => 'page:' . $this->products->getId(), 'label' => 'Produits', 'total' => 1, 'group' => 'page'],
            ['value' => 'page:' . $this->news->getId(), 'label' => 'Actualités', 'total' => 1, 'group' => 'page'],
        ], $this->sortedOptions($data['filters']['type']['options']));
        self::assertSame('facet', $data['filters']['type']['type']);

        // A category: the type facet still counts every type.
        $data = $this->api('GET', '/api/search-results?filters[search]=tracteur&filters[type][]=page:' . $this->news->getId(), locale: 'fr');
        self::assertSame(['Salon du tracteur'], array_column($data['member'], 'title'));
        self::assertSame(2, $data['filters']['type']['options'][0]['total']);

        // Types and categories are OR'd.
        $data = $this->api('GET', '/api/search-results?filters[type][]=page:' . $this->news->getId() . '&filters[type][]=page:' . $this->products->getId(), locale: 'fr');
        self::assertEqualsCanonicalizing(['Tracteur rouge', 'Salon du tracteur'], array_column($data['member'], 'title'));

        // An unknown type is ignored.
        $data = $this->api('GET', '/api/search-results?filters[type][]=news', locale: 'fr');
        self::assertSame(3, $data['totalItems']);
    }

    public function testTheLanguagesWithTheFallback(): void
    {
        // English: the English version of the tractor, the French one of the untranslated pages.
        $data = $this->api('GET', '/api/search-indices', locale: 'en');
        self::assertEqualsCanonicalizing(['Red tractor', 'Salon du tracteur', 'À propos'], array_column($data['member'], 'title'));

        $data = $this->api('GET', '/api/search-indices?filters[search]=tracteur', locale: 'en');
        self::assertSame(['Salon du tracteur'], array_column($data['member'], 'title'), 'Translated: searched in English only.');
    }

    /**
     * @param list<array{value: string, label: string, total: int, group: ?string}> $options
     *
     * @return list<array{value: string, label: string, total: int, group: ?string}>
     */
    private function sortedOptions(array $options): array
    {
        $categories = array_values(array_filter($options, static fn (array $option): bool => null !== $option['group']));
        usort($categories, fn (array $a, array $b): int => ($a['value'] === 'page:' . $this->products->getId() ? 0 : 1) <=> ($b['value'] === 'page:' . $this->products->getId() ? 0 : 1));

        return [...array_values(array_filter($options, static fn (array $option): bool => null === $option['group'])), ...$categories];
    }
}
