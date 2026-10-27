<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Admin;

use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;

final class MenuAdminTest extends ApiTestCase
{
    public function testCreateMenuOnTheCurrentSite(): void
    {
        $site = $this->site('main');
        $this->client->loginUser($this->fixtures->user('creator@example.com', ['view menus', 'edit menus']), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus/new');
        $this->client->submit($crawler->filter('form[name="menu"]')->form(['menu[code]' => 'header']));

        self::assertResponseRedirects('/admin/menus');
        $menu = $this->entityManager()->getRepository(Menu::class)->findOneBy(['code' => 'header']);
        self::assertInstanceOf(Menu::class, $menu);
        self::assertSame($site->getId(), $menu->getSite()?->getId());

        $this->client->followRedirect();
        self::assertSelectorTextContains('#gm-list-table', 'header');
    }

    public function testTheFormEndsWithTheFormIncludes(): void
    {
        $menu = $this->fixtures->menu('header', $this->site('main'));
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus/' . $menu->getId() . '/edit');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('.col-lg-8 #menu_code'));
        self::assertSame('header', $crawler->filter('.col-lg-4 > .gm-test-form-include')->text());
        self::assertSelectorTextNotContains('.crud-form', 'menu.name_s');
    }

    public function testCodeIsRequired(): void
    {
        $this->site('main');
        $this->client->loginUser($this->fixtures->user('creator@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus/new');
        $crawler = $this->client->submit($crawler->filter('form[name="menu"]')->form(['menu[code]' => '']));

        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, $crawler->filter('#menu_code.is-invalid'));
    }

    public function testMenuCannotBeCreatedWithoutSite(): void
    {
        $this->client->loginUser($this->fixtures->user('creator@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus/new');
        $this->client->submit($crawler->filter('form[name="menu"]')->form(['menu[code]' => 'header']));

        self::assertResponseStatusCodeSame(422);
        self::assertSelectorExists('form[name="menu"] .alert-danger, form[name="menu"] .invalid-feedback');
        self::assertSame(0, $this->entityManager()->getRepository(Menu::class)->count());
    }

    public function testListOnlyShowsTheMenusOfTheCurrentSite(): void
    {
        $main = $this->site('main');
        $other = $this->site('other', 'https://other.example.com');
        $this->fixtures->menu('main-header', $main);
        $this->fixtures->menu('other-header', $other);
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view menus']), 'admin');

        $this->client->request('GET', '/admin/menus', server: ['HTTP_X_SITE_ID' => (string) $other->getId()]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('#gm-list-table', 'other-header');
        self::assertSelectorTextNotContains('#gm-list-table', 'main-header');
    }

    public function testEditAndDelete(): void
    {
        $menu = $this->fixtures->menu('header', $this->site('main'));
        $this->client->loginUser($this->fixtures->user('editor@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/menus/' . $menu->getId() . '/edit');
        $this->client->submit($crawler->filter('form[name="menu"]')->form(['menu[code]' => 'footer']));
        self::assertResponseRedirects();
        $this->entityManager()->clear();
        self::assertSame('footer', $this->entityManager()->find(Menu::class, $menu->getId())?->getCode());

        $deleteUrl = '/admin/menus/' . $menu->getId() . '/delete';
        $token = $this->client->request('GET', '/admin/menus')->filter(\sprintf('[data-gm-delete-url="%s"]', $deleteUrl))->attr('data-gm-delete-token');
        $this->client->request('POST', $deleteUrl, ['_token' => (string) $token]);

        self::assertResponseRedirects('/admin/menus');
        self::assertNull($this->entityManager()->find(Menu::class, $menu->getId()));
    }

    public function testPermissionsAreRequired(): void
    {
        $menu = $this->fixtures->menu('header', $this->site('main'));

        $this->client->loginUser($this->fixtures->user('nobody@example.com', ['view sites']), 'admin');
        $this->client->request('GET', '/admin/menus');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view menus']), 'admin');
        $this->client->request('GET', '/admin/menus/' . $menu->getId() . '/edit');
        self::assertResponseStatusCodeSame(403);
    }

    public function testMenusAppearInTheCmsSectionOfTheAdminMenu(): void
    {
        $this->site('main');
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view menus']), 'admin');

        $crawler = $this->client->request('GET', '/admin/');

        self::assertCount(1, $crawler->filter('a[href="/admin/menus"]'));
    }
}
