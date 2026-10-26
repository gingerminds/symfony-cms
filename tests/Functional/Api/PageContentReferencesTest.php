<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Api;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MediaManagerBundle\Entity\File\File;
use Gingerminds\MediaManagerBundle\Entity\Media\Media;
use Gingerminds\MediaManagerBundle\File\Reference\FileReferenceRegistry;

/**
 * The files and medias of the blocks: resolved in the API (and its cache), usages of the library.
 */
final class PageContentReferencesTest extends ApiTestCase
{
    private Site $site;
    private Page $page;
    private File $image;
    private Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->site('main');
        $this->image = $this->fixtures->file('photo.png');
        $this->media = $this->fixtures->media('tractor', $this->fixtures->file('tractor.pdf', 'application/pdf'));
        $this->page = $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $this->translation()->setContent([
            ['uid' => 'a', 'type' => 'text_image', 'data' => ['image_position' => false, 'title' => 'Titre', 'text' => null, 'image' => $this->image->getId()]],
            ['uid' => 'b', 'type' => 'media_list', 'data' => ['title' => null, 'items' => [['media' => $this->media->getId(), 'subtitle' => 'Rouge'], ['media' => 999999, 'subtitle' => null]]]],
        ]);
        $this->entityManager()->flush();
    }

    public function testTheApiServesTheReferencedFilesAndMedias(): void
    {
        $content = $this->api('GET', '/api/pages/' . $this->page->getId())['content'];

        self::assertStatus(200);
        self::assertSame([
            'id' => $this->image->getId(),
            'url' => '/api/files/' . $this->image->getId(),
            'thumbnail_url' => '/api/files/' . $this->image->getId() . '/thumbnail',
            'mime_type' => 'image/png',
            'original_name' => 'photo.png',
            'size' => 10,
            'is_image' => true,
        ], $content[0]['data']['image']);

        $items = $content[1]['data']['items'];
        self::assertSame($this->media->getId(), $items[0]['media']['id']);
        self::assertSame('tractor', $items[0]['media']['code']);
        self::assertSame('application/pdf', $items[0]['media']['file_type'], 'As in GET /api/media.');
        self::assertSame('Rouge', $items[0]['subtitle']);
        self::assertNull($items[1]['media'], 'A deleted media.');

        // The stored content keeps the ids.
        $this->entityManager()->clear();
        self::assertSame($this->image->getId(), $this->translation()->getContent()[0]['data']['image'] ?? null);
    }

    public function testAChangeOfAMediaRefreshesTheCachedPages(): void
    {
        $this->api('GET', '/api/pages/' . $this->page->getId());
        $this->api('GET', '/api/pages/' . $this->page->getId());
        self::assertSame('HIT', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));

        $media = $this->entityManager()->find(Media::class, $this->media->getId());
        self::assertInstanceOf(Media::class, $media);
        $media->setCode('tractor-red');
        $this->entityManager()->flush();

        $content = $this->api('GET', '/api/pages/' . $this->page->getId())['content'];
        self::assertSame('MISS', $this->client->getResponse()->headers->get('X-Gingerminds-Cache'));
        self::assertSame('tractor-red', $content[1]['data']['items'][0]['media']['code']);
    }

    public function testTheFilesOfTheBlocksAreUsagesOfTheLibrary(): void
    {
        /** @var FileReferenceRegistry $references */
        $references = self::getContainer()->get('test.file_reference_registry');

        self::assertTrue($references->isUsed($this->image));
        $usages = $references->usages([$this->image])[$this->image->getId()];
        self::assertCount(1, $usages);
        self::assertSame('/admin/pages/' . $this->page->getId() . '/edit', $usages[0]->editUrl);
        self::assertContains($this->image->getId(), $references->usedIds());

        // A merge of duplicates points the content at the kept file.
        $kept = $this->fixtures->file('photo-1.png');
        self::assertSame(1, $references->replace($this->image, $kept));
        $this->entityManager()->clear();
        self::assertSame($kept->getId(), $this->translation()->getContent()[0]['data']['image'] ?? null);
    }

    private function translation(): PageTranslation
    {
        $translation = $this->entityManager()->getRepository(PageTranslation::class)->findOneBy(['translatable' => $this->page->getId()]);
        self::assertInstanceOf(PageTranslation::class, $translation);

        return $translation;
    }
}
