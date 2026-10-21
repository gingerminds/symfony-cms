<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Api;

use Gingerminds\CmsBundle\Entity\Menu\MenuItemTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MultisiteBundle\Entity\Language\Language;

final class MenuApiTest extends ApiTestCase
{
    public function testTheMenusOfTheSiteWithTheirActiveTree(): void
    {
        [$site, $fr] = $this->bilingualSite();
        $menu = $this->fixtures->menu('header', $site);
        $products = $this->fixtures->menuItem($menu, 'products', ['fr' => 'Produits', 'en' => 'Products'], position: 1, url: '/products');
        $this->fixtures->menuItem($menu, 'tractors', ['fr' => 'Tracteurs', 'en' => 'Tractors'], $products);
        $this->fixtures->menuItem($menu, 'hidden', ['fr' => 'Caché'], $products, active: false, position: 1);
        $this->fixtures->menuItem($menu, 'home', ['fr' => 'Accueil', 'en' => 'Home'], position: 0);
        $this->fixtures->menuItem($menu, 'draft', ['fr' => 'Brouillon'], active: false);
        $this->fixtures->menu('other', $this->fixtures->site('other', [$fr], $fr, 'https://other.example.com'));

        $data = $this->api('GET', '/api/menus', locale: 'en');

        self::assertStatus(200);
        self::assertSame(1, $data['totalItems']);
        $menuData = $data['member'][0];
        self::assertSame('header', $menuData['code']);
        self::assertSame(['Home', 'Products'], array_column($menuData['active_items'], 'name'));

        $productsData = $menuData['active_items'][1];
        self::assertSame('products', $productsData['code']);
        self::assertSame('/products', $productsData['url']);
        self::assertTrue($productsData['is_active']);
        self::assertFalse($productsData['is_target_blank']);
        self::assertSame(['Tractors'], array_column($productsData['children'], 'name'));
        self::assertSame([], $productsData['children'][0]['children']);

        // Item operation, another language.
        $data = $this->api('GET', '/api/menus/' . $menu->getId(), locale: 'fr');
        self::assertStatus(200);
        self::assertSame(['Accueil', 'Produits'], array_column($data['active_items'], 'name'));
    }

    public function testTheMenuOperationsDocumentTheContextHeaders(): void
    {
        $this->client->request('GET', '/api/docs.jsonopenapi', server: ['HTTP_ACCEPT' => 'application/vnd.openapi+json']);
        self::assertResponseIsSuccessful();
        $paths = json_decode((string) $this->client->getResponse()->getContent(), true, flags: \JSON_THROW_ON_ERROR)['paths'];

        foreach (['/api/menus', '/api/menus/{id}'] as $path) {
            $headers = array_column(array_filter($paths[$path]['get']['parameters'], static fn (array $parameter): bool => 'header' === $parameter['in']), 'name');
            self::assertSame(['X-Site-Id', 'Accept-Language'], $headers, $path);
        }
    }

    public function testAChangeOfAnItemRefreshesTheCachedMenu(): void
    {
        $menu = $this->fixtures->menu('header', $this->site('main'));
        $item = $this->fixtures->menuItem($menu, 'home', ['fr' => 'Accueil']);

        $this->api('GET', '/api/menus/' . $menu->getId());
        $data = $this->api('GET', '/api/menus/' . $menu->getId());
        self::assertSame('HIT', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));
        self::assertSame('Accueil', $data['active_items'][0]['name']);

        // The client reboots the kernel: the translation of the current entity manager.
        $translation = $this->entityManager()->getRepository(MenuItemTranslation::class)->findOneBy(['translatable' => $item->getId()]);
        self::assertInstanceOf(MenuItemTranslation::class, $translation);
        $translation->setName('Bienvenue');
        $this->entityManager()->flush();

        $data = $this->api('GET', '/api/menus/' . $menu->getId());
        self::assertSame('Bienvenue', $data['active_items'][0]['name']);
    }

    public function testTheSitesExposeTheirMainMenu(): void
    {
        $site = $this->site('main');
        $other = $this->site('other', 'https://other.example.com');
        $menu = $this->fixtures->menu('header', $site);
        $otherMenu = $this->fixtures->menu('other-header', $other);
        $site->setMainMenu($menu);
        $other->setMainMenu($otherMenu);
        $this->entityManager()->flush();

        $data = $this->api('GET', '/api/sites');

        self::assertStatus(200);
        // The menu of another site is not loaded (filtered out): its id only.
        self::assertSame([$menu->getId(), $otherMenu->getId()], array_column($data['member'], 'main_menu_id'));
    }

    /**
     * @return array{Site, Language}
     */
    private function bilingualSite(): array
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');

        return [$this->fixtures->site('main', [$fr, $en], $fr), $fr];
    }
}
