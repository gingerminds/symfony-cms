<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Api;

use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * `gingerminds_multisite.translation_fallback: false`: an untranslated item keeps its place with
 * null translated fields, the admin still falls back.
 */
final class MenuApiNoFallbackTest extends ApiTestCase
{
    public function testUntranslatedItemsHaveNullFieldsOnTheApiOnly(): void
    {
        $fr = $this->fixtures->language('fr');
        $en = $this->fixtures->language('en');
        $site = $this->fixtures->site('main', [$fr, $en], $en);
        $menu = $this->fixtures->menu('header', $site);
        $parent = $this->fixtures->menuItem($menu, 'products', ['en' => 'Products', 'fr' => 'Produits'], url: '/products');
        $this->fixtures->menuItem($menu, 'tractors', ['en' => 'Tractors'], $parent, url: '/tractors');

        $data = $this->api('GET', '/api/menus/' . $menu->getId(), locale: 'fr');

        self::assertStatus(200);
        self::assertSame('Produits', $data['active_items'][0]['name']);
        $child = $data['active_items'][0]['children'][0];
        self::assertSame('tractors', $child['code']);
        self::assertNull($child['name']);
        self::assertNull($child['url']);

        $data = $this->api('GET', '/api/menus/' . $menu->getId(), locale: 'en');
        self::assertSame('Tractors', $data['active_items'][0]['children'][0]['name']);

        // The admin falls back to the default language.
        $this->client->loginUser($this->fixtures->user('reader@example.com', superAdmin: true), 'admin');
        $crawler = $this->client->request('GET', '/admin/menus/' . $menu->getId() . '/items', server: ['HTTP_ACCEPT_LANGUAGE' => 'fr']);
        self::assertSame(['Produits', 'Tractors'], $crawler->filter('.sortable-item .fw-medium')->each(static fn ($node): string => $node->text()));
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_no_fallback', ...$options]);
    }
}
