<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Admin;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

final class PageAdminTest extends ApiTestCase
{
    private const string BASE = '/admin/pages';

    private Site $site;
    private PageCategory $products;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->site('main');
        $this->products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
    }

    public function testTheListAndTheCategoryChoiceBeforeCreation(): void
    {
        $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']], $this->products, 'published');
        $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']]);
        $this->fixtures->pageCategory($this->site, 'news', ['fr' => ['Actualités', 'actualites']], position: 1);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view pages', 'edit pages']), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '?sortBy=code');

        self::assertResponseIsSuccessful();
        $rows = $crawler->filter('#gm-list-table tbody tr');
        self::assertSame(['about', 'tractor'], $rows->each(static fn (Crawler $row): string => $row->filter('td')->eq(1)->text()));
        self::assertSame(['À propos', 'Tracteur X'], $rows->each(static fn (Crawler $row): string => $row->filter('td')->eq(2)->text()));
        self::assertSame('Produits', $rows->eq(1)->filter('td')->eq(3)->text());
        self::assertSame('bg-success', explode(' ', (string) $rows->eq(1)->filter('.badge')->attr('class'))[1]);
        // The modal: no category, then the tree.
        self::assertSame(
            [self::BASE . '/new', self::BASE . '/new?category_id=' . $this->products->getId()],
            \array_slice($crawler->filter('#gm-cms-choose-page-category a')->each(static fn (Crawler $link): string => (string) $link->attr('href')), 0, 2),
        );
        // The status filter offers the project statuses too.
        self::assertCount(1, $crawler->filter('select[name="filters[status][]"] option[value="review"]'));
        // Pages first in the CMS section.
        self::assertSame('/admin/pages', $crawler->filter('#gm-sidebar a[href^="/admin/page"], #gm-sidebar a[href="/admin/menus"]')->first()->attr('href'));

        $crawler = $this->client->request('GET', self::BASE . '?filters[search]=Tracteur');
        self::assertSame(['tractor'], $crawler->filter('#gm-list-table tbody tr')->each(static fn (Crawler $row): string => $row->filter('td')->eq(1)->text()));
    }

    public function testCreateAPublishedPageInACategory(): void
    {
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view pages', 'edit pages']), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/new?category_id=' . $this->products->getId());
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="page"]')->form();
        self::assertSame((string) $this->products->getId(), $form['page[category]']->getValue());
        // A new page: draft, publish or submit (project transition).
        self::assertSame(['draft', 'published', 'review'], $crawler->filter('#page_status option')->each(static fn (Crawler $option): string => (string) $option->attr('value')));
        $slug = $crawler->filter('#' . $this->fieldId('slug'));
        self::assertSame(['/produits/'], $slug->closest('.input-group')?->filter('.input-group-text')->each(static fn (Crawler $text): string => $text->text()));
        self::assertSame('#' . $this->fieldId('title'), $slug->attr('data-gm-cms-slug-sync-source-value'));
        self::assertSame('["bold","italic","underline","link"]', $crawler->filter('#' . $this->fieldId('hook'))->closest('[data-controller="gm-cms-wysiwyg"]')?->attr('data-gm-cms-wysiwyg-extensions-value'));

        $this->client->submit($form, [
            'page[code]' => 'tractor',
            'page[status]' => 'published',
            $this->translationField('title') => 'Tracteur X',
            $this->translationField('slug') => 'tracteur-x',
            $this->translationField('hook') => '<p onclick="alert(1)">Le meilleur <strong>tracteur</strong>.</p><script>alert(1)</script>',
        ]);

        self::assertResponseRedirects(self::BASE);
        $page = $this->entityManager()->getRepository(Page::class)->findOneBy(['code' => 'tractor']);
        self::assertInstanceOf(Page::class, $page);
        self::assertSame('published', $page->getStatus());
        self::assertNotNull($page->getPublishedAt());
        self::assertSame($this->products->getId(), $page->getCategory()?->getId());
        self::assertSame('produits/tracteur-x', $page->getFullPath());
        $translation = $page->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $translation);
        self::assertSame($this->site->getId(), $translation->getSite()?->getId());
        self::assertSame('<p>Le meilleur <strong>tracteur</strong>.</p>', $translation->getHook(), 'Sanitized.');
    }

    public function testTheTransitionsOfThePublicationWorkflow(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']], status: 'published');
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/' . $page->getId() . '/edit');
        self::assertSame(['published', 'draft', 'archived'], $crawler->filter('#page_status option')->each(static fn (Crawler $option): string => (string) $option->attr('value')));
        $this->client->submit($crawler->filter('form[name="page"]')->form(['page[status]' => 'archived']));
        self::assertResponseRedirects();

        $this->entityManager()->clear();
        $page = $this->entityManager()->find(Page::class, $page->getId());
        self::assertSame('archived', $page?->getStatus());
        self::assertNotNull($page?->getArchivedAt());
        self::assertNull($page?->getPublishedAt());

        // From archived: back to draft only (no direct publication).
        $crawler = $this->client->request('GET', self::BASE . '/' . $page?->getId() . '/edit');
        self::assertSame(['archived', 'draft'], $crawler->filter('#page_status option')->each(static fn (Crawler $option): string => (string) $option->attr('value')));
        $form = $crawler->filter('form[name="page"]')->form();
        $form['page[status]']->disableValidation()->select('published');
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidPages(): iterable
    {
        yield 'code taken on the site' => [['page[code]' => 'tractor'], '#page_code'];
        yield 'title required' => [['title' => ''], 'title'];
        yield 'slug format' => [['slug' => 'Tracteur X'], 'slug'];
        yield 'slug taken in the category' => [['page[category]' => 'products', 'slug' => 'tracteur-x'], 'slug'];
        yield 'blank slug taken in the category' => [['page[category]' => 'products', 'slug' => ''], 'slug'];
        yield 'single page category taken' => [['page[category]' => 'single'], '#page_category'];
    }

    /**
     * @param array<string, string> $values `title` / `slug`: the default language translation;
     *                                      `page[category]`: a category code
     */
    #[DataProvider('invalidPages')]
    public function testInvalidPagesAreRejected(array $values, string $invalidField): void
    {
        $single = $this->fixtures->pageCategory($this->site, 'single', ['fr' => ['Unique', 'unique']], unique: true);
        $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']], $this->products);
        $this->fixtures->page($this->site, 'products-home', ['fr' => ['Nos produits', null]], $this->products);
        $this->fixtures->page($this->site, 'single-page', ['fr' => ['Page unique', 'page']], $single);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $categories = ['products' => $this->products, 'single' => $single];
        $fields = ['page[code]' => 'mower', $this->translationField('title') => 'Tondeuse', $this->translationField('slug') => 'tondeuse'];

        foreach ($values as $field => $value) {
            $fields[match ($field) {
                'title', 'slug' => $this->translationField($field),
                default => $field,
            }] = 'page[category]' === $field ? (string) $categories[$value]->getId() : $value;
        }

        $crawler = $this->client->request('GET', self::BASE . '/new');
        $crawler = $this->client->submit($crawler->filter('form[name="page"]')->form($fields));

        self::assertResponseStatusCodeSame(422);
        $selector = str_starts_with($invalidField, '#') ? $invalidField : '#' . $this->fieldId($invalidField);
        self::assertCount(1, $crawler->filter($selector . '.is-invalid'));
    }

    public function testTheSameSlugInAnotherCategoryIsAllowed(): void
    {
        $services = $this->fixtures->pageCategory($this->site, 'services', ['fr' => ['Services', 'services']]);
        $this->fixtures->page($this->site, 'products-presentation', ['fr' => ['Présentation', 'presentation']], $this->products);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/new?category_id=' . $services->getId());
        $this->client->submit($crawler->filter('form[name="page"]')->form([
            'page[code]' => 'services-presentation',
            $this->translationField('title') => 'Présentation',
            $this->translationField('slug') => 'presentation',
        ]));

        self::assertResponseRedirects(self::BASE);
    }

    public function testPreviewLinksPerLanguageAndFrontUrl(): void
    {
        $en = $this->fixtures->language('en');
        $this->site->setLanguages([$this->site->getDefaultLanguage() ?? $en, $en]);
        $this->site->setFrontUrlValues(['https://front.example.com/', 'https://other-front.example.com']);
        $this->entityManager()->flush();
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']]);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/' . $page->getId() . '/edit');

        $links = $crawler->filter('.col-lg-4 a[target="_blank"]')->each(static fn (Crawler $link): string => (string) $link->attr('href'));
        self::assertSame([
            'https://front.example.com/preview/page/' . $page->getId(),
            'https://other-front.example.com/preview/page/' . $page->getId(),
            'https://front.example.com/en/preview/page/' . $page->getId(),
            'https://other-front.example.com/en/preview/page/' . $page->getId(),
        ], $links);

        // A new page has no preview.
        $crawler = $this->client->request('GET', self::BASE . '/new');
        self::assertCount(0, $crawler->filter('.col-lg-4 a[target="_blank"]'));
    }

    public function testTheLayoutOfTheTranslations(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']]);
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/' . $page->getId() . '/edit');

        $pane = $crawler->filter('#page-pane-translations');
        foreach (['title', 'slug'] as $field) {
            self::assertCount(1, $pane->filter('.col-lg-8 #' . $this->fieldId($field)), $field . ' in the main column');
        }
        self::assertCount(1, $pane->filter('.col-lg-4 #' . $this->fieldId('hook')), 'The hook in the sidebar.');
        self::assertGreaterThan(0, $pane->filter('.col-lg-4 [id^="' . $this->fieldId('mainVisual') . '"]')->count(), 'Visuals in the sidebar.');
        self::assertCount(0, $pane->filter('.col-lg-8 [id^="' . $this->fieldId('mainVisual') . '"]'));
        // The `page_translation_form_sidebar` include sees the form of the language (named after its id).
        self::assertSame((string) $this->site->getDefaultLanguage()?->getId(), $pane->filter('.col-lg-4 > .gm-test-form-include')->attr('data-form'));

        // One column for the categories.
        $crawler = $this->client->request('GET', '/admin/page-categories/' . $this->products->getId() . '/edit');
        self::assertCount(0, $crawler->filter('#page-category-pane-translations .col-lg-4'));
        self::assertCount(1, $crawler->filter('#page-category-pane-translations .col-12 [id$="_prefix"]'));
    }

    public function testPermissionsFormIncludesAndDelete(): void
    {
        $page = $this->fixtures->page($this->site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']]);

        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view pages']), 'admin');
        $this->client->request('GET', self::BASE . '/' . $page->getId() . '/edit');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', self::BASE . '/' . $page->getId() . '/edit');
        $pane = $crawler->filter('#page-pane-general');
        self::assertSame('tractor', $pane->filter('.col-lg-8 > .gm-test-form-include')->text());
        self::assertSame('page', $pane->filter('.col-lg-4 > .gm-test-form-include')->attr('data-form'));

        $deleteUrl = self::BASE . '/' . $page->getId() . '/delete';
        $token = (string) $this->client->request('GET', self::BASE)->filter(\sprintf('[data-gm-delete-url="%s"]', $deleteUrl))->attr('data-gm-delete-token');
        $this->client->request('POST', $deleteUrl, ['_token' => $token]);
        self::assertResponseRedirects(self::BASE);
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(Page::class, $page->getId()));
    }

    private function fieldId(string $field): string
    {
        return \sprintf('page_translations_%d_%s', $this->site->getDefaultLanguage()?->getId(), $field);
    }

    private function translationField(string $field): string
    {
        return \sprintf('page[translations][%d][%s]', $this->site->getDefaultLanguage()?->getId(), $field);
    }
}
