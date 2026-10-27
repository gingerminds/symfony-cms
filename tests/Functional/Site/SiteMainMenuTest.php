<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Site;

use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

final class SiteMainMenuTest extends ApiTestCase
{
    public function testTheCmsSiteIsTheMultisiteSiteEntity(): void
    {
        self::assertSame(Site::class, $this->entityManager()->getClassMetadata(SiteInterface::class)->getName());

        $association = $this->entityManager()->getClassMetadata(Site::class)->getAssociationMapping('mainMenu');
        self::assertSame(Menu::class, $association->targetEntity);
        self::assertSame('main_menu_id', $association->joinColumns[0]->name);
        self::assertSame('SET NULL', $association->joinColumns[0]->onDelete);
    }

    public function testTheMainMenuIsChosenAmongTheMenusOfTheSite(): void
    {
        $site = $this->site('main');
        $other = $this->site('other', 'https://other.example.com');
        $header = $this->fixtures->menu('header', $site);
        $footer = $this->fixtures->menu('footer', $site);
        $foreign = $this->fixtures->menu('foreign', $other);
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');

        // The admin works on the other site: the choices still are the menus of the edited one.
        $crawler = $this->client->request('GET', '/admin/sites/' . $site->getId() . '/edit', server: ['HTTP_X_SITE_ID' => (string) $other->getId()]);

        self::assertResponseIsSuccessful();
        $options = $crawler->filter('#site_main_menu option')->each(static fn ($option): string => (string) $option->attr('value'));
        self::assertSame(['', (string) $footer->getId(), (string) $header->getId()], $options);
        self::assertSelectorNotExists('#site_main_menu_help');
        self::assertCount(1, $crawler->filter('.col-lg-4 #site_main_menu'), 'The field is in the right column of the site form.');

        $form = $crawler->filter('form[name="site"]')->form();
        $form['site[main_menu]']->select((string) $header->getId());
        $this->client->submit($form);

        self::assertResponseRedirects();
        $this->entityManager()->clear();
        self::assertSame($header->getId(), $this->entityManager()->find(Site::class, $site->getId())?->getMainMenu()?->getId());

        // A menu of another site is not a valid choice.
        $crawler = $this->client->request('GET', '/admin/sites/' . $site->getId() . '/edit');
        $form = $crawler->filter('form[name="site"]')->form();
        $form['site[main_menu]']->disableValidation()->select((string) $foreign->getId());
        $this->client->submit($form);

        self::assertResponseStatusCodeSame(422);
        $this->entityManager()->clear();
        self::assertSame($header->getId(), $this->entityManager()->find(Site::class, $site->getId())?->getMainMenu()?->getId());
    }

    public function testANewSiteHasNoMenuToChoose(): void
    {
        $this->site('main');
        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');

        $crawler = $this->client->request('GET', '/admin/sites/new');

        self::assertResponseIsSuccessful();
        self::assertSame([''], $crawler->filter('#site_main_menu option')->each(static fn ($option): string => (string) $option->attr('value')));
        self::assertSelectorExists('#site_main_menu_help');
    }
}
