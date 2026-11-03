<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Search;

use Gingerminds\CmsBundle\Entity\Page\BasePage;
use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SearchResourceRegistryTest extends KernelTestCase
{
    public function testTheTypesAreTheIndexedEntities(): void
    {
        $registry = self::getContainer()->get('test.search_resources');
        self::assertInstanceOf(SearchResourceRegistry::class, $registry);

        $resource = $registry->get('page');
        self::assertEquals(new SearchResource('page', Page::class, 'page.name_p', 'GingermindsCms', 'category', BasePage::GROUP_LIST), $resource);
        self::assertSame(['page'], array_keys($registry->all()));
        self::assertSame($resource, $registry->forEntity(new Page()));
        self::assertNull($registry->forEntity(new PageCategory()));
        self::assertNull($registry->get('news'));
    }

    public function testThePlainTextOfAField(): void
    {
        self::assertSame('Le meilleur tracteur, enfin.', SearchDocumentFactory::plainText("<p>Le <strong>meilleur</strong>\n tracteur,&nbsp;enfin.</p>"));
        self::assertSame('a b', SearchDocumentFactory::plainText('<p>a</p><p>b</p>'));
        self::assertSame('', SearchDocumentFactory::plainText(null));
        self::assertSame('', SearchDocumentFactory::plainText(['array']));
    }
}
