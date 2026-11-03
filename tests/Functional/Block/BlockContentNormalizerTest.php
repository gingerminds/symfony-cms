<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * The stored shape of a content: the data of each block submitted to its form.
 */
final class BlockContentNormalizerTest extends KernelTestCase
{
    public function testTheContentGetsItsStoredShape(): void
    {
        $normalizer = self::getContainer()->get('gingerminds_cms.block.content_normalizer');
        self::assertInstanceOf(BlockContentNormalizer::class, $normalizer);

        $content = $normalizer->normalize([
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Titre', 'text' => '<p onclick="x()">Texte</p><script>x()</script>', 'removed' => 'old'], 'extra' => 1],
            ['uid' => 'b', 'type' => 'test_links', 'data' => [
                'heading' => '',
                'highlighted' => '1',
                'links' => [3 => ['label' => 'B', 'url' => '/b', 'old' => 1], 1 => ['label' => 'A']],
            ]],
            ['uid' => 'c', 'type' => 'unknown', 'data' => ['kept' => true]],
            'not a block',
        ]);

        self::assertSame([
            // Removed fields dropped, the WYSIWYG HTML sanitized again; `subtitle`: the project extension.
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Titre', 'subtitle' => null, 'text' => '<p>Texte</p>']],
            // empty_data of a missing field, empty string null, toggle boolean, rows in a list.
            ['uid' => 'b', 'type' => 'test_links', 'data' => [
                'heading' => null,
                'style' => 'light',
                'highlighted' => true,
                'links' => [['label' => 'B', 'url' => '/b'], ['label' => 'A', 'url' => null]],
            ]],
            // An unknown block and an invalid item are kept for the validation.
            ['uid' => 'c', 'type' => 'unknown', 'data' => ['kept' => true]],
            'not a block',
        ], $content);
    }
}
