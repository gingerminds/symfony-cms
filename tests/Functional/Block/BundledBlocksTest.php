<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Gingerminds\CmsBundle\Block\Type\Video;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MediaManagerBundle\Entity\File\File;
use Gingerminds\MediaManagerBundle\Entity\Media\Media;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The blocks of the bundle in the block modal: their form, their validation, their preview.
 */
final class BundledBlocksTest extends ApiTestCase
{
    use BlockModalTrait;

    private File $image;
    private File $pdf;
    private Media $media;

    protected function setUp(): void
    {
        parent::setUp();
        $this->image = $this->fixtures->file('photo.png');
        $this->pdf = $this->fixtures->file('guide.pdf', 'application/pdf');
        $this->media = $this->fixtures->media('tractor', $this->image);
        $this->loginEditor();
    }

    /**
     * @return iterable<string, array{string, callable(self): array<string, mixed>, string}>
     */
    public static function validBlocks(): iterable
    {
        yield 'text_image' => ['text_image', static fn (self $test): array => [
            'image_position' => '1', 'title' => 'Titre', 'text' => '<p>Texte</p>', 'image' => $test->image->getId(),
        ], 'img[src$="/thumbnail"]'];
        yield 'media' => ['media', static fn (self $test): array => ['title' => 'Guide', 'file' => $test->pdf->getId()], '.bi-file-earmark'];
        yield 'video' => ['video', static fn (): array => ['title' => 'Démo', 'embed_code' => 'https://youtu.be/dQw4w9WgXcQ'], 'iframe[src$="/embed/dQw4w9WgXcQ"]'];
        yield 'cards' => ['cards', static fn (self $test): array => [
            'title' => 'Cartes', 'cards' => [['title' => 'A', 'description' => 'Desc', 'image' => $test->image->getId()]],
        ], '.border img'];
        yield 'slider' => ['slider', static fn (self $test): array => ['slides' => [['image' => $test->image->getId()]]], 'img'];
        yield 'link_list' => ['link_list', static fn (self $test): array => [
            'title' => 'Liens', 'links' => [['label' => 'Contact', 'url' => '/contact'], ['label' => 'Site', 'url' => 'https://example.com', 'image' => $test->image->getId()]],
        ], 'li img'];
        yield 'media_list' => ['media_list', static fn (self $test): array => ['items' => [['media' => (string) $test->media->getId(), 'subtitle' => 'Rouge']]], 'li img'];
        yield 'faq' => ['faq', static fn (): array => ['title' => 'FAQ', 'items' => [['question' => 'Pourquoi ?', 'answer' => '<p>Parce que.</p>']]], 'details summary'];
    }

    /**
     * @param callable(self): array<string, mixed> $data
     */
    #[DataProvider('validBlocks')]
    public function testAValidBlockGivesItsPreview(string $key, callable $data, string $previewSelector): void
    {
        $form = new Crawler($this->form($key)['html']);
        self::assertCount(1, $form->filter('form'));

        $json = $this->validate($key, $data($this));

        self::assertResponseIsSuccessful((string) ($json['html'] ?? ''));
        self::assertCount(1, new Crawler($json['preview'])->filter($previewSelector)->first(), $json['preview']);
    }

    public function testTheStoredDataOfTheReferences(): void
    {
        $json = $this->validate('text_image', ['title' => 'Titre', 'image' => $this->image->getId()]);
        self::assertSame(['title' => 'Titre', 'image_position' => false, 'text' => null, 'image' => $this->image->getId()], $json['data']);

        $json = $this->validate('media_list', ['items' => [['media' => (string) $this->media->getId()]]]);
        self::assertSame(['title' => null, 'items' => [['media' => $this->media->getId(), 'subtitle' => null]]], $json['data'], 'A media id as an integer.');
    }

    public function testTheReferencesAreChecked(): void
    {
        // An image only.
        $json = $this->validate('text_image', ['title' => 'Titre', 'image' => $this->pdf->getId()]);
        self::assertResponseStatusCodeSame(422);
        self::assertCount(1, new Crawler($json['html'])->filter('.invalid-feedback, .form-error-message'), $json['html']);

        // Every type.
        $this->validate('media', ['title' => 'Guide', 'file' => $this->pdf->getId()]);
        self::assertResponseIsSuccessful();

        $this->validate('media_list', ['items' => [['media' => '999999']]]);
        self::assertResponseStatusCodeSame(422);

        $json = $this->validate('slider', ['slides' => [['image' => '']]]);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $json['html']);
    }

    public function testALinkIsAnAbsoluteUrlOrAPathOfTheSite(): void
    {
        foreach (['/contact', '/produits?page=2#top', 'https://example.com/a'] as $url) {
            $this->validate('link_list', ['title' => 'Liens', 'links' => [['label' => 'Lien', 'url' => $url]]]);
            self::assertResponseIsSuccessful($url);
        }

        foreach (['contact', '//example.com', 'javascript:alert(1)'] as $url) {
            $json = $this->validate('link_list', ['title' => 'Liens', 'links' => [['label' => 'Lien', 'url' => $url]]]);
            self::assertResponseStatusCodeSame(422, $url);
            self::assertStringContainsString('Saisissez une URL complète', $json['html'], $url);
        }
    }

    public function testTheYoutubeIdOfAnEmbedCode(): void
    {
        foreach (['dQw4w9WgXcQ', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&t=10', 'https://youtu.be/dQw4w9WgXcQ', 'https://www.youtube.com/embed/dQw4w9WgXcQ', 'https://youtube.com/shorts/dQw4w9WgXcQ'] as $code) {
            self::assertSame('dQw4w9WgXcQ', Video::youtubeId($code), $code);
        }

        self::assertNull(Video::youtubeId('https://vimeo.com/123'));
        self::assertNull(Video::youtubeId(null));
    }
}
