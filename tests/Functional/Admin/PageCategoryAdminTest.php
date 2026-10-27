<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Admin;

use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

final class PageCategoryAdminTest extends ApiTestCase
{
    private const string BASE = '/admin/page-categories';

    private Site $site;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->site('main');
    }

    public function testTheTreeIsOrderedByPositionThenCode(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']], position: 1);
        $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $this->fixtures->pageCategory($this->site, 'news', ['fr' => ['Actualités', 'actualites']], position: 1);
        $this->fixtures->pageCategory($this->site, 'about', ['fr' => ['À propos', 'a-propos']], position: 0);
        $this->fixtures->pageCategory($this->site('other', 'https://other.example.com'), 'other', ['fr' => ['Autre site', 'autre']]);
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view page_categories']), 'admin');

        $crawler = $this->client->request('GET', self::BASE);

        self::assertResponseIsSuccessful();
        self::assertSame(['À propos', 'Actualités', 'Produits', 'Tracteurs'], $crawler->filter('.sortable-item .fw-medium')->each(static fn (Crawler $node): string => $node->text()));
        self::assertSame('/produits/tracteurs', $crawler->filter('.sortable-level .sortable-level .gm-cms-category-path')->text());
        self::assertCount(0, $crawler->filter('[data-controller="gm-sortable-tree"]'), 'Read only: no drag & drop.');
        self::assertCount(1, $crawler->filter('#gm-sidebar a[href="/admin/page-categories"]'));
    }

    public function testCreateACategoryUnderAParent(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view page_categories', 'edit page_categories']), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/new?parent_id=' . $products->getId());
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="page_category"]')->form();
        self::assertSame((string) $products->getId(), $form['page_category[parent]']->getValue());
        // The prefix: slugified from the name (overwritten on creation), the pages path in its help.
        $prefix = $crawler->filter('#' . $this->fieldId('prefix'));
        self::assertSame('gm-cms-slug-sync', $prefix->attr('data-controller'));
        self::assertSame('#' . $this->fieldId('name'), $prefix->attr('data-gm-cms-slug-sync-source-value'));
        self::assertSame('true', $prefix->attr('data-gm-cms-slug-sync-overwrite-value'));
        $group = $prefix->closest('.input-group');
        self::assertNotNull($group);
        self::assertSame(['/produits/', '/'], $group->filter('.input-group-text')->each(static fn (Crawler $text): string => $text->text()));

        $this->client->submit($form, [
            'page_category[code]' => 'mowers',
            'page_category[unique]' => '1',
            $this->translationField('name') => 'Tondeuses',
            $this->translationField('prefix') => 'tondeuses',
        ]);

        self::assertResponseRedirects(self::BASE);
        $category = $this->entityManager()->getRepository(PageCategory::class)->findOneBy(['code' => 'mowers']);
        self::assertInstanceOf(PageCategory::class, $category);
        self::assertSame($products->getId(), $category->getParent()?->getId());
        self::assertSame($this->site->getId(), $category->getSite()?->getId());
        self::assertSame(1, $category->getPosition(), 'Last of its level.');
        self::assertTrue($category->isUnique());
        self::assertSame('produits/tondeuses', $category->getFullPath());
        $translation = $category->getTranslations()->first();
        self::assertInstanceOf(PageCategoryTranslation::class, $translation);
        self::assertSame($this->site->getId(), $translation->getSite()?->getId(), 'The translation copies the site of the category.');
    }

    public function testABlankPrefixIsStoredAsNullAndTheEditFormDoesNotOverwriteIt(): void
    {
        $category = $this->fixtures->pageCategory($this->site, 'default', ['fr' => ['Défaut', 'defaut']]);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/' . $category->getId() . '/edit');
        self::assertSame('false', $crawler->filter('#' . $this->fieldId('prefix'))->attr('data-gm-cms-slug-sync-overwrite-value'));
        // A root category: its pages go under /<prefix>/.
        $group = $crawler->filter('#' . $this->fieldId('prefix'))->closest('.input-group');
        self::assertSame(['/', '/'], $group?->filter('.input-group-text')->each(static fn (Crawler $text): string => $text->text()));
        $this->client->submit($crawler->filter('form[name="page_category"]')->form([$this->translationField('prefix') => '  ']));

        self::assertResponseRedirects();
        $this->entityManager()->clear();
        $category = $this->entityManager()->find(PageCategory::class, $category->getId());
        self::assertNull($category?->getPrefix());
        self::assertSame('', $category?->getFullPath());
    }

    /**
     * @return iterable<string, array{array<string, string>, string}>
     */
    public static function invalidSubmissions(): iterable
    {
        yield 'code taken on the site' => [['page_category[code]' => 'products'], '#page_category_code'];
        yield 'name required in the default language' => [['page_category[code]' => 'mowers', 'name' => ''], 'name'];
        yield 'prefix format' => [['page_category[code]' => 'mowers', 'prefix' => 'Nos produits/'], 'prefix'];
        yield 'prefix taken by a sibling' => [['page_category[code]' => 'mowers', 'prefix' => 'produits'], 'prefix'];
    }

    /**
     * @param array<string, string> $values `name` / `prefix`: the default language translation
     */
    #[DataProvider('invalidSubmissions')]
    public function testInvalidCategoriesAreRejected(array $values, string $invalidField): void
    {
        $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        // Same code and prefix on another site, or under another parent: allowed.
        $this->fixtures->pageCategory($this->site('other', 'https://other.example.com'), 'mowers', ['fr' => ['Tondeuses', 'tondeuses']]);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $fields = ['page_category[code]' => 'mowers', $this->translationField('name') => 'Tondeuses', $this->translationField('prefix') => 'tondeuses'];

        foreach ($values as $field => $value) {
            $fields[\in_array($field, ['name', 'prefix'], true) ? $this->translationField($field) : $field] = $value;
        }

        $crawler = $this->client->request('GET', self::BASE . '/new');
        $crawler = $this->client->submit($crawler->filter('form[name="page_category"]')->form($fields));

        self::assertResponseStatusCodeSame(422);
        $selector = str_starts_with($invalidField, '#') ? $invalidField : '#' . $this->fieldId($invalidField);
        self::assertCount(1, $crawler->filter($selector . '.is-invalid'));
        self::assertNull($this->entityManager()->getRepository(PageCategory::class)->findOneBy(['code' => 'mowers', 'site' => $this->site->getId()]));
    }

    public function testACategoryWithChildrenNeedsAPrefix(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $transparent = $this->fixtures->pageCategory($this->site, 'range', ['fr' => ['Gamme', null]]);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        // Emptying the prefix of a parent.
        $crawler = $this->client->request('GET', self::BASE . '/' . $products->getId() . '/edit');
        $crawler = $this->client->submit($crawler->filter('form[name="page_category"]')->form([$this->translationField('prefix') => '']));
        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter('#' . $this->fieldId('prefix') . '.is-invalid'));

        // A transparent category (legacy data) cannot become a parent.
        $crawler = $this->client->request('GET', self::BASE . '/new');
        $crawler = $this->client->submit($crawler->filter('form[name="page_category"]')->form([
            'page_category[code]' => 'mowers',
            'page_category[parent]' => (string) $transparent->getId(),
            $this->translationField('name') => 'Tondeuses',
            $this->translationField('prefix') => 'tondeuses',
        ]));
        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter('#page_category_parent.is-invalid'));
        self::assertStringContainsString('fr', $crawler->filter('#page_category_parent')->ancestors()->first()->text());
    }

    public function testTheParentIsChosenInTheTreeWithoutCycle(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $tractors = $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $this->fixtures->pageCategory($this->site, 'news', ['fr' => ['Actualités', 'actualites']], position: 1);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', self::BASE . '/new');
        self::assertSame(['', 'Produits', '— Tracteurs', 'Actualités'], $this->parentChoices($crawler));

        $crawler = $this->client->request('GET', self::BASE . '/' . $products->getId() . '/edit');
        self::assertSame(['', 'Actualités'], $this->parentChoices($crawler));

        $form = $crawler->filter('form[name="page_category"]')->form();
        $form['page_category[parent]']->disableValidation()->select((string) $tractors->getId());
        $this->client->submit($form);
        self::assertResponseStatusCodeSame(422);
    }

    public function testReorderALevel(): void
    {
        $first = $this->fixtures->pageCategory($this->site, 'first', ['fr' => ['Premier', 'premier']], position: 0);
        $second = $this->fixtures->pageCategory($this->site, 'second', ['fr' => ['Second', 'second']], position: 1);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view page_categories', 'edit page_categories']), 'admin');

        $tree = $this->client->request('GET', self::BASE)->filter('[data-controller="gm-sortable-tree"]');
        self::assertSame(self::BASE . '/reorder', $tree->attr('data-gm-sortable-tree-url-value'));
        $token = (string) $tree->attr('data-gm-sortable-tree-csrf-token-value');

        $this->reorder([(int) $second->getId(), (int) $first->getId()], 'invalid');
        self::assertResponseStatusCodeSame(403);

        $this->reorder([(int) $second->getId(), (int) $first->getId()], $token);
        self::assertResponseIsSuccessful();
        $this->entityManager()->clear();
        self::assertSame(1, $this->entityManager()->find(PageCategory::class, $first->getId())?->getPosition());
        self::assertSame(0, $this->entityManager()->find(PageCategory::class, $second->getId())?->getPosition());
    }

    public function testDeleteMovesTheChildrenToTheRoot(): void
    {
        $products = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);
        $tractors = $this->fixtures->pageCategory($this->site, 'tractors', ['fr' => ['Tracteurs', 'tracteurs']], $products);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $deleteUrl = self::BASE . '/' . $products->getId() . '/delete';
        $token = (string) $this->client->request('GET', self::BASE)->filter(\sprintf('[data-gm-delete-url="%s"]', $deleteUrl))->attr('data-gm-delete-token');
        $this->client->request('POST', $deleteUrl, ['_token' => $token]);

        self::assertResponseRedirects(self::BASE);
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(PageCategory::class, $products->getId()));
        self::assertNull($this->entityManager()->find(PageCategory::class, $tractors->getId())?->getParent());
    }

    public function testPermissionsAndFormIncludes(): void
    {
        $category = $this->fixtures->pageCategory($this->site, 'products', ['fr' => ['Produits', 'produits']]);

        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view page_categories']), 'admin');
        $this->client->request('GET', self::BASE . '/' . $category->getId() . '/edit');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', self::BASE . '/' . $category->getId() . '/edit');
        $pane = $crawler->filter('#page-category-pane-general');
        self::assertSame('products', $pane->filter('.col-lg-8 > .gm-test-form-include')->text());
        self::assertSame('page_category', $pane->filter('.col-lg-4 > .gm-test-form-include')->attr('data-form'));
        self::assertCount(1, $pane->filter('.col-lg-4 #page_category_parent'), 'The parent is in the sidebar.');
        // The slug sync controller is registered on every admin page.
        self::assertStringContainsString('gm-cms-slug-sync', $crawler->filter('head')->html());
    }

    private function fieldId(string $field): string
    {
        return \sprintf('page_category_translations_%d_%s', $this->site->getDefaultLanguage()?->getId(), $field);
    }

    private function translationField(string $field): string
    {
        return \sprintf('page_category[translations][%d][%s]', $this->site->getDefaultLanguage()?->getId(), $field);
    }

    /**
     * @return list<string>
     */
    private function parentChoices(Crawler $crawler): array
    {
        return $crawler->filter('#page_category_parent option')->each(static fn (Crawler $option): string => '' === $option->attr('value') ? '' : $option->text());
    }

    /**
     * @param list<int> $ids
     */
    private function reorder(array $ids, string $token): void
    {
        $this->client->request('POST', self::BASE . '/reorder', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode(['ids' => $ids, 'parent_id' => null], \JSON_THROW_ON_ERROR));
    }
}
