<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Reference\BlockReferenceResolverInterface;
use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Gingerminds\MediaManagerBundle\Form\Media\MediaSelectType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The reference fields found in the block forms (FilePickerType, MediaSelectType, in the rows too).
 */
final class ContentReferenceResolverTest extends KernelTestCase
{
    private FakeReferenceResolver $files;
    private FakeReferenceResolver $medias;
    private ContentReferenceResolver $resolver;

    protected function setUp(): void
    {
        $registry = self::getContainer()->get('test.block_registry');
        self::assertInstanceOf(BlockRegistry::class, $registry);
        $this->files = new FakeReferenceResolver(['f1' => 'File 1', 'f2' => 'File 2']);
        $this->medias = new FakeReferenceResolver(['7' => 'Media 7']);
        $this->resolver = new ContentReferenceResolver(
            $registry,
            new ServiceLocator([
                FilePickerType::class => fn (): BlockReferenceResolverInterface => $this->files,
                MediaSelectType::class => fn (): BlockReferenceResolverInterface => $this->medias,
            ]),
            new ArrayAdapter(),
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
