<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Search;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Search\SearchDocumentFactory;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use PHPUnit\Framework\TestCase;

final class SearchResourceRegistryTest extends TestCase
{
    public function testTheTypesAndTheirFields(): void
    {
        $registry = new SearchResourceRegistry([
            'page' => [
                'entity' => Page::class,
                'label' => 'page.name_p',
                'translation_domain' => 'GingermindsCms',
                'category' => 'category',
                'group' => 'page:list',
                'fields' => ['title' => ['weight' => 3], 'hook' => ['weight' => 0], 'code' => ['weight' => 1]],
            ],
        ]);

        $resource = $registry->get('page');
        self::assertNotNull($resource);
        self::assertSame(['title' => 3, 'code' => 1], $resource->fields, 'A weight 0 removes a field.');
        self::assertSame($resource, $registry->forEntity(new Page()));
        self::assertNull($registry->forEntity(new \stdClass()));
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
