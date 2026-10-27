<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Type\TitleText;
use Gingerminds\CmsBundle\Tests\Application\Block\LinksBlock;
use Gingerminds\CmsBundle\Wysiwyg\WysiwygSanitizer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class BlockContentNormalizerTest extends TestCase
{
    public function testTheContentGetsItsStoredShape(): void
    {
        $normalizer = new BlockContentNormalizer(
            new BlockRegistry(
                new ServiceLocator([
                    'test_links' => static fn (): BlockInterface => new LinksBlock(),
                    'title_text' => static fn (): BlockInterface => new TitleText(),
                ]),
                new ServiceLocator([]),
            ),
            new WysiwygSanitizer(),
        );

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
            // Removed fields dropped, the WYSIWYG HTML sanitized again.
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Titre', 'text' => '<p>Texte</p>']],
            // Defaults added, empty string null, toggle boolean, rows in a list.
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
