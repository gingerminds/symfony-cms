<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Page;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class PageUrlTest extends ApiTestCase
{
    private Site $site;
    private Language $fr;
    private Language $en;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fr = $this->fixtures->language('fr');
        $this->en = $this->fixtures->language('en');
        $this->site = $this->fixtures->site('main', [$this->fr, $this->en], $this->fr);
    }

    public function testOnlyThePublishedPagesHaveAPathPerTranslatedLanguage(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits'], 'en' => ['Products', 'products']]);
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur'], 'en' => ['', 'tractor']], $products);
        self::assertSame([], $this->paths($page), 'A draft has no URL.');

        $page->setStatus('published');
        $this->entityManager()->flush();
        self::assertSame(['fr' => 'produits/tracteur'], $this->paths($page), 'A blank title is not translated.');

        $this->translation($page, 'en')->setTitle('Tractor');
        $this->entityManager()->flush();
        self::assertSame(['fr' => 'produits/tracteur', 'en' => 'products/tractor'], $this->paths($page));

        $this->translation($page, 'fr')->setSlug(null);
        $page->setCategory(null);
        $this->entityManager()->flush();
        self::assertSame(['fr' => '', 'en' => 'tractor'], $this->paths($page), 'The site home page in French.');

        $page->setStatus('draft');
        $this->entityManager()->flush();
        self::assertSame([], $this->paths($page));
    }

    public function testACategoryChangeSyncsItsSubtree(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $tractors = $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $page = $this->fixtures->page($this->site, 'x', ['fr' => ['Tracteur X', 'x']], $tractors, 'published');
        self::assertSame(['fr' => 'produits/tracteurs/x'], $this->paths($page));

        $translation = $products->getTranslations()->first();
        self::assertInstanceOf(PageCategoryTranslation::class, $translation);
        $translation->setPrefix('nos-produits');
        $this->entityManager()->flush();
        self::assertSame(['fr' => 'nos-produits/tracteurs/x'], $this->paths($page));

        // Deleted category: its pages go to the root.
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $deleteUrl = '/admin/page-categories/' . $tractors->getId() . '/delete';
        $token = (string) $this->client->request('GET', '/admin/page-categories')->filter(\sprintf('[data-gm-delete-url="%s"]', $deleteUrl))->attr('data-gm-delete-token');
        $this->client->request('POST', $deleteUrl, ['_token' => $token]);
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $page = $this->entityManager()->find(Page::class, $page->getId());
        self::assertNull($page?->getCategory());
        self::assertSame(['fr' => 'x'], $this->paths($page));
    }

    public function testAPathTakenByAnotherPageIsKeptAndReported(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $range = $this->fixtures->pageCategory($this->site, 'range', ['fr' => ['Gamme', 'gamme']], $products);
        $owner = $this->fixtures->page($this->site, 'owner', ['fr' => ['Présentation', 'presentation']], $products, 'published');
        $page = $this->fixtures->page($this->site, 'page', ['fr' => ['Présentation gamme', 'presentation']], $range, 'published');
        self::assertSame(['fr' => 'produits/gamme/presentation'], $this->paths($page));

        // Made transparent (no sub-category): its page would take the path of the other one.
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', '/admin/page-categories/' . $range->getId() . '/edit');
        $this->client->submit($crawler->filter('form[name="page_category"]')->form([
            \sprintf('page_category[translations][%d][prefix]', $this->fr->getId()) => '',
        ]));
        self::assertResponseRedirects();

        $crawler = $this->client->followRedirect();
        self::assertStringContainsString('/produits/presentation', $crawler->filter('.alert-warning')->text(''));
        self::assertSame(['fr' => 'produits/gamme/presentation'], $this->paths($page), 'The former path is kept.');
        self::assertSame(['fr' => 'produits/presentation'], $this->paths($owner));
    }

    public function testThePageFormRejectsAPathTakenByAPublishedPage(): void
    {
        $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $range = $this->fixtures->pageCategory($this->site, 'range', ['fr' => ['Gamme', null]]);
        // Same path through a transparent category; a draft is allowed.
        $page = $this->fixtures->page($this->site, 'other-about', ['fr' => ['À propos bis', 'a-propos']], $range);
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/pages/' . $page->getId() . '/edit');
        $crawler = $this->client->submit($crawler->filter('form[name="page"]')->form(['page[status]' => 'published']));

        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter(\sprintf('#page_translations_%d_slug.is-invalid', $this->fr->getId())));
    }

    public function testTheCommandSyncsEveryPage(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur', 'tracteur']], status: 'published');
        $this->entityManager()->createQuery('DELETE FROM ' . PageUrl::class . ' u')->execute();
        self::assertSame([], $this->paths($page));

        $tester = new CommandTester(new Application(self::$kernel ?? self::bootKernel())->find('gingerminds:cms:page-urls:sync'));
        $tester->execute([]);

        $tester->assertCommandIsSuccessful();
        self::assertStringContainsString('1 page(s) synced', $tester->getDisplay());
        $this->entityManager()->clear();
        self::assertSame(['fr' => 'tracteur'], $this->paths($this->entityManager()->find(Page::class, $page->getId())));
        self::assertSame([], self::getContainer()->get(PageUrlSyncer::class)->getConflicts());
    }

    /**
     * @return array<string, string>
     */
    private function paths(?Page $page): array
    {
        $paths = [];

        foreach ($this->entityManager()->getRepository(PageUrl::class)->findBy(['page' => $page?->getId()], ['id' => 'ASC']) as $url) {
            $paths[(string) $url->getLanguage()->getIso()] = $url->getPath();
        }

        return $paths;
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
}
