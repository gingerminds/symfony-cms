<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\CmsBundle\Block\Type\MediaList;
use Gingerminds\CmsBundle\Block\Type\TextImage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ContentReferenceResolverTest extends TestCase
{
    private FakeReferenceResolver $files;
    private FakeReferenceResolver $medias;
    private ContentReferenceResolver $resolver;

    protected function setUp(): void
    {
        $this->files = new FakeReferenceResolver(['f1' => 'File 1', 'f2' => 'File 2']);
        $this->medias = new FakeReferenceResolver(['7' => 'Media 7']);
        $this->resolver = new ContentReferenceResolver(
            new BlockRegistry(
                new ServiceLocator([
                    'text_image' => static fn (): BlockInterface => new TextImage(),
                    'media_list' => static fn (): BlockInterface => new MediaList(),
                ]),
                new ServiceLocator([]),
            ),
            new ServiceLocator([
                'file' => fn (): BlockReferenceResolverInterface => $this->files,
                'media' => fn (): BlockReferenceResolverInterface => $this->medias,
            ]),
        );
    }

    public function testTheReferencesAreLoadedOncePerTypeAndNormalized(): void
    {
        $content = [
            ['uid' => 'a', 'type' => 'text_image', 'data' => ['title' => 'A', 'image' => 'f1']],
            ['uid' => 'b', 'type' => 'text_image', 'data' => ['title' => 'B', 'image' => 'gone']],
            ['uid' => 'c', 'type' => 'media_list', 'data' => ['items' => [['media' => 7, 'subtitle' => 'x'], ['media' => 8], ['media' => null]]]],
            ['uid' => 'd', 'type' => 'unknown', 'data' => ['image' => 'f2']],
        ];

        $resolved = $this->resolver->resolve($content);

        self::assertSame([['f1', 'gone']], $this->files->loads, 'One load for every file of the content.');
        self::assertSame([[7, 8]], $this->medias->loads);
        self::assertSame(['title' => 'A', 'image' => ['name' => 'File 1']], $resolved[0]['data'] ?? null);
        self::assertSame(['title' => 'B', 'image' => null], $resolved[1]['data'] ?? null, 'A missing file: null.');
        self::assertSame([['media' => ['name' => 'Media 7'], 'subtitle' => 'x'], ['media' => null], ['media' => null]], $resolved[2]['data']['items'] ?? null);
        self::assertSame($content[3], $resolved[3], 'An unknown block is kept.');
        self::assertNull($this->resolver->resolve(null));
    }

    public function testThePreviewsGetTheEntities(): void
    {
        $entities = $this->resolver->entities([['type' => 'text_image', 'data' => ['image' => 'f2', 'title' => 'T']]]);

        self::assertEquals(['image' => new \ArrayObject(['name' => 'File 2']), 'title' => 'T'], $entities[0]['data'] ?? null);
        self::assertSame([], $this->medias->loads, 'No media: no query.');
    }
}
