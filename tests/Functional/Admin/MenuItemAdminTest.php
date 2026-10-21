<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Admin;

use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Entity\Menu\MenuItem;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class MenuItemAdminTest extends ApiTestCase
{
    private Site $site;
    private Menu $menu;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->site('main');
        $this->menu = $this->fixtures->menu('header', $this->site);
    }

    public function testTheTreeListsTheItemsOfTheMenu(): void
    {
        $products = $this->fixtures->menuItem($this->menu, 'products', ['fr' => 'Produits'], position: 1);
        $this->fixtures->menuItem($this->menu, 'tractors', ['fr' => 'Tracteurs'], $products);
        $this->fixtures->menuItem($this->menu, 'home', ['fr' => 'Accueil'], active: false, position: 0);
        $this->fixtures->menuItem($this->fixtures->menu('footer', $this->site), 'legal', ['fr' => 'Mentions']);
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view menus', 'view menu_items']), 'admin');

        $crawler = $this->client->request('GET', $this->base());

        self::assertResponseIsSuccessful();
        self::assertSame(['Accueil', 'Produits', 'Tracteurs'], $crawler->filter('.sortable-item .fw-medium')->each(static fn (Crawler $node): string => $node->text()));
        self::assertCount(1, $crawler->filter('.sortable-level .sortable-level [data-item-id] .fw-medium:contains("Tracteurs")'));
        self::assertCount(1, $crawler->filter('.sortable-item .badge'), 'The inactive item is flagged.');
        self::assertSelectorTextNotContains('.card-body', 'Mentions');
        // Read only: no drag & drop, no creation.
        self::assertCount(0, $crawler->filter('[data-controller="gm-sortable-tree"]'));
        self::assertCount(0, $crawler->filter(\sprintf('a[href="%s/new"]', $this->base())));
    }

    public function testTheMenuListLinksToItsItems(): void
    {
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view menus', 'view menu_items']), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus');

        self::assertCount(1, $crawler->filter(\sprintf('a[href="%s"]', $this->base())));
    }

    public function testCreateAnItemUnderAParent(): void
    {
        $products = $this->fixtures->menuItem($this->menu, 'products', ['fr' => 'Produits']);
        $this->fixtures->menuItem($this->menu, 'tractors', ['fr' => 'Tracteurs'], $products);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view menu_items', 'edit menu_items']), 'admin');

        $crawler = $this->client->request('GET', $this->base() . '/new?parent_id=' . $products->getId());
        self::assertResponseIsSuccessful();
        $form = $crawler->filter('form[name="menu_item"]')->form();
        self::assertSame((string) $products->getId(), $form['menu_item[parent]']->getValue());

        $this->client->submit($form, [
            'menu_item[code]' => 'mowers',
            'menu_item[active]' => '1',
            'menu_item[noFollow]' => '1',
            $this->translationField('name') => 'Tondeuses',
            $this->translationField('url') => '/tondeuses',
        ]);

        self::assertResponseRedirects($this->base());
        $item = $this->entityManager()->getRepository(MenuItem::class)->findOneBy(['code' => 'mowers']);
        self::assertInstanceOf(MenuItem::class, $item);
        self::assertSame($products->getId(), $item->getParent()?->getId());
        self::assertSame($this->site->getId(), $item->getSite()?->getId());
        self::assertSame(1, $item->getPosition(), 'Last of its level.');
        self::assertTrue($item->isActive());
        self::assertTrue($item->isNoFollow());
        self::assertSame('Tondeuses', $item->getName());
        self::assertSame('/tondeuses', $item->getUrl());
    }

    public function testTheGeneralTabEndsWithTheFormIncludes(): void
    {
        $item = $this->fixtures->menuItem($this->menu, 'home', ['fr' => 'Accueil']);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', $this->base() . '/' . $item->getId() . '/edit');

        self::assertResponseIsSuccessful();
        $pane = $crawler->filter('#menu-item-pane-general');
        self::assertSame('home', $pane->filter('.col-lg-8 > .gm-test-form-include')->text());
        self::assertSame('menu_item', $pane->filter('.col-lg-4 > .gm-test-form-include')->attr('data-form'));
        self::assertCount(0, $crawler->filter('#menu-item-pane-translations .gm-test-form-include'));
    }

    public function testTheNameIsRequiredInTheDefaultLanguage(): void
    {
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', $this->base() . '/new');
        $crawler = $this->client->submit($crawler->filter('form[name="menu_item"]')->form([
            'menu_item[code]' => 'contact',
            $this->translationField('name') => '',
        ]));

        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('active', (string) $crawler->filter('#menu-item-tab-translations')->attr('class'), 'The invalid tab is open.');
        self::assertSame(0, $this->entityManager()->getRepository(MenuItem::class)->count());
    }

    public function testTheParentIsChosenInTheTreeOfTheMenuWithoutCycle(): void
    {
        $products = $this->fixtures->menuItem($this->menu, 'products', ['fr' => 'Produits']);
        $tractors = $this->fixtures->menuItem($this->menu, 'tractors', ['fr' => 'Tracteurs'], $products);
        $this->fixtures->menuItem($this->menu, 'home', ['fr' => 'Accueil'], position: 1);
        $foreign = $this->fixtures->menuItem($this->fixtures->menu('footer', $this->site), 'legal', ['fr' => 'Mentions']);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', $this->base() . '/new');
        self::assertSame(['', 'Produits', '— Tracteurs', 'Accueil'], $this->parentChoices($crawler));

        // Editing "Produits": neither itself nor "Tracteurs" (its child).
        $crawler = $this->client->request('GET', $this->base() . '/' . $products->getId() . '/edit');
        self::assertSame(['', 'Accueil'], $this->parentChoices($crawler));

        // A forged parent (child of the item, item of another menu) is rejected.
        foreach ([$tractors, $foreign] as $parent) {
            $form = $crawler->filter('form[name="menu_item"]')->form();
            $form['menu_item[parent]']->disableValidation()->select((string) $parent->getId());
            $crawler = $this->client->submit($form);
            self::assertResponseStatusCodeSame(422);
        }

        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(MenuItem::class, $products->getId())?->getParent());
    }

    public function testAnItemOfAnotherMenuOrSiteIsNotFound(): void
    {
        $foreign = $this->fixtures->menuItem($this->fixtures->menu('footer', $this->site), 'legal', ['fr' => 'Mentions']);
        $otherSiteMenu = $this->fixtures->menu('other', $this->site('other', 'https://other.example.com'));
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $this->client->request('GET', $this->base() . '/' . $foreign->getId() . '/edit');
        self::assertResponseStatusCodeSame(404);

        $this->client->request('GET', '/admin/menus/' . $otherSiteMenu->getId() . '/items');
        self::assertResponseStatusCodeSame(404);
    }

    public function testReorderALevel(): void
    {
        $first = $this->fixtures->menuItem($this->menu, 'first', ['fr' => 'Premier'], position: 0);
        $second = $this->fixtures->menuItem($this->menu, 'second', ['fr' => 'Second'], position: 1);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view menu_items', 'edit menu_items']), 'admin');

        $crawler = $this->client->request('GET', $this->base());
        $tree = $crawler->filter('[data-controller="gm-sortable-tree"]');
        self::assertSame($this->base() . '/reorder', $tree->attr('data-gm-sortable-tree-url-value'));
        $token = (string) $tree->attr('data-gm-sortable-tree-csrf-token-value');

        $this->reorder([(int) $second->getId(), (int) $first->getId()], 'invalid');
        self::assertResponseStatusCodeSame(403);

        $this->reorder([(int) $second->getId(), (int) $first->getId()], $token);
        self::assertResponseIsSuccessful();

        $this->entityManager()->clear();
        self::assertSame(1, $this->entityManager()->find(MenuItem::class, $first->getId())?->getPosition());
        self::assertSame(0, $this->entityManager()->find(MenuItem::class, $second->getId())?->getPosition());
    }

    public function testDeleteMovesTheChildrenToTheRoot(): void
    {
        $products = $this->fixtures->menuItem($this->menu, 'products', ['fr' => 'Produits']);
        $tractors = $this->fixtures->menuItem($this->menu, 'tractors', ['fr' => 'Tracteurs'], $products);
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $deleteUrl = $this->base() . '/' . $products->getId() . '/delete';
        $token = (string) $this->client->request('GET', $this->base())->filter(\sprintf('[data-gm-delete-url="%s"]', $deleteUrl))->attr('data-gm-delete-token');
        $this->client->request('POST', $deleteUrl, ['_token' => $token]);

        self::assertResponseRedirects($this->base());
        $this->entityManager()->clear();
        self::assertNull($this->entityManager()->find(MenuItem::class, $products->getId()));
        self::assertNull($this->entityManager()->find(MenuItem::class, $tractors->getId())?->getParent());
    }

    private function base(): string
    {
        return '/admin/menus/' . $this->menu->getId() . '/items';
    }

    private function translationField(string $field): string
    {
        return \sprintf('menu_item[translations][%d][%s]', $this->site->getDefaultLanguage()?->getId(), $field);
    }

    /**
     * @return list<string>
     */
    private function parentChoices(Crawler $crawler): array
    {
        return $crawler->filter('#menu_item_parent option')->each(static fn (Crawler $option): string => '' === $option->attr('value') ? '' : $option->text());
    }

    /**
     * @param list<int> $ids
     */
    private function reorder(array $ids, string $token): void
    {
        $this->client->request('POST', $this->base() . '/reorder', server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X_CSRF_TOKEN' => $token], content: json_encode(['ids' => $ids, 'parent_id' => null], \JSON_THROW_ON_ERROR));
    }
}
