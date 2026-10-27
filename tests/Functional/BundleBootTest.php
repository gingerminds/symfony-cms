<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional;

use Gingerminds\CmsBundle\Entity\Menu\Menu;
use Gingerminds\CmsBundle\Entity\Menu\MenuItem;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Symfony\Component\HttpKernel\KernelInterface;

final class BundleBootTest extends ApiTestCase
{
    public function testBundleIsRegisteredWithItsConfiguration(): void
    {
        /** @var KernelInterface $kernel */
        $kernel = self::getContainer()->get('kernel');

        self::assertArrayHasKey('GingermindsCmsBundle', $kernel->getBundles());
        self::assertSame(['default', 'extended', 'minimal', 'full'], array_keys(self::getContainer()->getParameter('gingerminds_cms.wysiwyg.presets')));
        self::assertTrue(self::getContainer()->getParameter('gingerminds_cms.slug_overwrite.create'));
        self::assertFalse(self::getContainer()->getParameter('gingerminds_cms.slug_overwrite.edit'));
        self::assertSame([], self::getContainer()->getParameter('gingerminds_cms.blocks.disabled'));
        self::assertSame([], self::getContainer()->getParameter('gingerminds_cms.blocks.order'));
        self::assertSame(Menu::class, self::getContainer()->getParameter('gingerminds_cms.resource.menu.entity'));
        self::assertSame(MenuItem::class, self::getContainer()->getParameter('gingerminds_cms.resource.menu_item.entity'));
        self::assertSame(PageCategory::class, self::getContainer()->getParameter('gingerminds_cms.resource.page_category.entity'));
    }

    public function testAdminDashboardRenders(): void
    {
        $this->site('main');

        $this->client->loginUser($this->fixtures->user('admin@example.com', superAdmin: true), 'admin');
        $this->client->request('GET', '/admin/');

        self::assertResponseIsSuccessful();
    }
}
