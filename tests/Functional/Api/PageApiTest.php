<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Api;

use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;

final class PageApiTest extends ApiTestCase
{
    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $fr = $this->fixtures->language('fr');
        $this->site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
    }

    public function testTheCollectionListsThePublishedPagesWithTheirFacets(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits'], 'en' => ['Products', 'products']]);
        $tractor = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur'], 'en' => ['Tractor', 'tractor']], $products, 'published');
        $tractor->setPublishedAt(new \DateTimeImmutable('2026-03-01 10:00'));
        $this->entityManager()->flush();
        $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $this->fixtures->page($this->site, 'draft', ['fr' => ['Brouillon', 'brouillon']]);

        $data = $this->api('GET', '/api/pages', locale: 'en');

        self::assertStatus(200);
        self::assertEqualsCanonicalizing(['about', 'tractor'], array_column($data['member'], 'code'));
        $tractor = array_column($data['member'], null, 'code')['tractor'];
        self::assertSame('Tractor', $tractor['title']);
        self::assertSame('published', $tractor['status']);
        self::assertSame(['fr' => 'produits/tracteur', 'en' => 'products/tractor'], $tractor['switch_lang']);
        self::assertArrayNotHasKey('content', $tractor, 'The content is on the item only.');
        self::assertSame([['value' => $products->getId(), 'label' => 'Products', 'total' => 1]], $data['filters']['category']['options']);
        self::assertSame('date', $data['filters']['publishedAt']['type']);

        $data = $this->api('GET', '/api/pages?filters[category]=' . $products->getId());
        self::assertSame(['tractor'], array_column($data['member'], 'code'));
    }

    public function testAnItemOfAnyStatusIsReadByItsId(): void
    {
        $page = $this->fixtures->page($this->site, 'draft', ['fr' => ['Brouillon', 'brouillon']]);

        $data = $this->api('GET', '/api/pages/' . $page->getId());

        self::assertStatus(200);
        self::assertSame('draft', $data['status']);
        self::assertSame(['fr' => 'brouillon'], $data['switch_lang'], 'Computed for an unpublished page.');
        self::assertNull($data['content']);
    }

    public function testTheContentIsServedAsStoredWithTheTranslationFallback(): void
    {
        $content = [['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour', 'subtitle' => null, 'text' => '<p>Texte</p>']]];
        $page = $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $translation = $page->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $translation);
        $translation->setContent($content);
        $this->entityManager()->flush();

        self::assertSame($content, $this->api('GET', '/api/pages/' . $page->getId())['content']);
        self::assertSame($content, $this->api('GET', '/api/pages/' . $page->getId(), locale: 'en')['content'], 'Untranslated: the default language one.');
    }

    public function testAPublishedPageIsFoundByItsPath(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits'], 'en' => ['Products', 'products']]);
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur'], 'en' => ['Tractor', 'tractor']], $products, 'published');
        $this->fixtures->page($this->site, 'draft', ['fr' => ['Brouillon', 'brouillon']]);

        $data = $this->api('GET', '/api/pages/by-slug/products/tractor', locale: 'en');
        self::assertStatus(200);
        self::assertSame($page->getId(), $data['id']);
        self::assertSame('Tractor', $data['title']);

        // The French path, asked in English: the page in English.
        $data = $this->api('GET', '/api/pages/by-slug/produits/tracteur', locale: 'en');
        self::assertStatus(200);
        self::assertSame('Tractor', $data['title']);

        $this->api('GET', '/api/pages/by-slug/brouillon');
        self::assertStatus(404);
        $this->api('GET', '/api/pages/by-slug/products/unknown');
        self::assertStatus(404);
    }

    public function testTheSwitchLangOfAPublishedPageIsItsStoredPaths(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur']], status: 'published');
        // A stored path stays while the computed one would conflict.
        $this->entityManager()->createQuery('UPDATE Gingerminds\CmsBundle\Entity\Page\PageUrl u SET u.path = :path')->execute(['path' => 'ancien']);
        $this->entityManager()->clear();

        self::assertSame(['fr' => 'ancien'], $this->api('GET', '/api/pages/' . $page->getId())['switch_lang']);
        self::assertSame($page->getId(), $this->api('GET', '/api/pages/by-slug/ancien')['id']);
    }

    public function testThePageOperationsDocumentTheContextHeaders(): void
    {
        $this->client->request('GET', '/api/docs.jsonopenapi', server: ['HTTP_ACCEPT' => 'application/vnd.openapi+json']);
        self::assertResponseIsSuccessful();
        $paths = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR)['paths'];

        foreach (['/api/pages', '/api/pages/{id}', '/api/pages/by-slug/{slug}'] as $path) {
            $headers = array_column(array_filter($paths[$path]['get']['parameters'], static fn (array $parameter): bool => 'header' === $parameter['in']), 'name');
            self::assertSame(['X-Site-Id', 'Accept-Language'], $headers, $path);
        }
    }

    public function testAChangeOfATranslationRefreshesTheCachedPage(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur']], status: 'published');

        $this->api('GET', '/api/pages/by-slug/tracteur');
        $this->api('GET', '/api/pages/by-slug/tracteur');
        self::assertSame('HIT', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));

        $translation = $this->entityManager()->getRepository(PageTranslation::class)->findOneBy(['translatable' => $page->getId()]);
        self::assertInstanceOf(PageTranslation::class, $translation);
        $translation->setTitle('Tracteur X');
        $this->entityManager()->flush();

        self::assertSame('Tracteur X', $this->api('GET', '/api/pages/by-slug/tracteur')['title']);
    }
}
