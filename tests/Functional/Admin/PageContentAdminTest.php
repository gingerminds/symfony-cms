<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Admin;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Site\Entity\Site;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;

/**
 * The block content of the pages (BlockCanvasType): the canvas, the normalization and the full
 * revalidation on save.
 */
final class PageContentAdminTest extends ApiTestCase
{
    private Site $site;
    private Page $page;

    protected function setUp(): void
    {
        parent::setUp();
        $this->site = $this->site('main');
        $this->page = $this->fixtures->page($this->site, 'about', ['fr' => ['À propos', 'a-propos']]);
        $this->client->loginUser($this->fixtures->user('editor@example.com', ['view pages', 'edit pages']), 'admin');
        $this->client->setServerParameter('HTTP_ACCEPT_LANGUAGE', 'fr');
    }

    public function testTheCanvasShowsTheStoredBlocks(): void
    {
        $this->translation()->setContent([
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour', 'text' => '<p>Texte</p>']],
            ['uid' => 'b', 'type' => 'gone', 'data' => ['x' => 1]],
        ]);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->editUrl());

        self::assertResponseIsSuccessful();
        $canvas = $crawler->filter('[data-controller="gm-cms-blocks"]');
        self::assertSame('FR', $canvas->attr('data-gm-cms-blocks-language-value'));
        self::assertSame('content', $canvas->attr('data-gm-cms-blocks-field-value'));
        self::assertSame('/admin/pages/blocks/__key__/form', $canvas->attr('data-gm-cms-blocks-form-url-value'));
        $blocks = $canvas->filter('[data-gm-cms-blocks-target="list"] .gm-cms-block');
        self::assertCount(2, $blocks);
        self::assertStringContainsString('is-collapsed', (string) $blocks->eq(0)->attr('class'));
        self::assertSame('Titre + texte', $blocks->eq(0)->filter('[data-role="label"]')->text());
        self::assertStringContainsString('bi-fonts', (string) $blocks->eq(0)->filter('[data-role="icon"]')->attr('class'), 'The project override.');
        self::assertSame('Bonjour', $blocks->eq(0)->filter('h3')->text());
        self::assertSame(
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour', 'subtitle' => null, 'text' => '<p>Texte</p>']],
            json_decode((string) $blocks->eq(0)->attr('data-block'), true),
            'The data with the defaults of the fields added since.',
        );
        self::assertSame('Type de bloc inconnu : gone', $blocks->eq(1)->filter('.alert-warning')->text());
        self::assertNotNull($blocks->eq(1)->filter('[data-action="gm-cms-blocks#edit"]')->attr('hidden'), 'An unknown block cannot be edited.');
        // The picker: enabled blocks by weight (TitleText 0, LinksBlock 5, then the other bundle blocks).
        self::assertSame(['title_text', 'test_links', 'text_image', 'media', 'video', 'cards', 'slider', 'link_list', 'media_list', 'faq'], $canvas->filter('[data-block-key]')->each(static fn (Crawler $button): string => (string) $button->attr('data-block-key')));
    }

    public function testSavingNormalizesTheContent(): void
    {
        $this->save([
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour', 'text' => '<p onclick="x()">Texte</p>', 'removed' => 'old']],
            ['uid' => 'b', 'type' => 'test_links', 'data' => ['heading' => 'Liens', 'highlighted' => 'on', 'links' => [2 => ['label' => 'A', 'url' => '/a']]]],
        ]);

        self::assertResponseRedirects();
        $this->entityManager()->clear();
        self::assertSame([
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour', 'subtitle' => null, 'text' => '<p>Texte</p>']],
            ['uid' => 'b', 'type' => 'test_links', 'data' => ['heading' => 'Liens', 'style' => 'light', 'highlighted' => true, 'links' => [['label' => 'A', 'url' => '/a']]]],
        ], $this->translation()->getContent());

        $this->save([]);
        $this->entityManager()->clear();
        self::assertNull($this->translation()->getContent(), 'No block: null.');
    }

    public function testTheWholeContentIsRevalidated(): void
    {
        $crawler = $this->save([
            ['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => '']],
            ['uid' => 'b', 'type' => 'test_links', 'data' => ['heading' => 'Liens', 'style' => 'other', 'links' => [['label' => 'A']]]],
            ['uid' => 'c', 'type' => 'gone', 'data' => []],
            ['type' => 'title_text', 'data' => ['title' => 'Sans uid']],
        ]);

        self::assertResponseStatusCodeSame(422);
        $blocks = $crawler->filter('[data-gm-cms-blocks-target="list"] .gm-cms-block');
        self::assertCount(4, $blocks, 'The submitted blocks are kept.');
        $errors = $blocks->each(static fn (Crawler $block): array => $block->filter('.gm-cms-block-errors li')->each(static fn (Crawler $item): string => $item->text()));
        self::assertSame([
            ['Titre : Cette valeur ne doit pas être vide.'],
            ["Style : Cette valeur doit être l'un des choix proposés.", 'Liens 1 › URL : Cette valeur ne doit pas être vide.'],
            ['Type de bloc inconnu : gone.'],
            ['Ce bloc est invalide.'],
        ], $errors);
        self::assertStringNotContainsString('is-collapsed', (string) $blocks->eq(0)->attr('class'), 'An invalid block is open.');
        self::assertSame('Liens', $blocks->eq(1)->filter('strong')->text());
        // The translations tab is the invalid one.
        self::assertStringContainsString('text-danger', (string) $crawler->filter('#page-tab-translations')->attr('class'));

        $crawler = $this->save('{');
        self::assertResponseStatusCodeSame(422);
        self::assertSame('Le contenu est invalide.', $crawler->filter('[data-controller="gm-cms-blocks"]')->ancestors()->first()->filter('.invalid-feedback')->text());
    }

    public function testAnOptionalLanguageWithoutBlockStaysBlank(): void
    {
        $en = $this->fixtures->language('en');
        $this->site->setLanguages([$this->site->getDefaultLanguage(), $en]);
        $this->entityManager()->flush();

        $crawler = $this->client->request('GET', $this->editUrl());
        $field = $crawler->filter(\sprintf('input[name="page[translations][%d][content]"]', $en->getId()));
        self::assertCount(1, $field);
        self::assertSame('', (string) $field->attr('value'), 'No block: an empty field.');

        $this->client->submit($crawler->filter('form[name="page"]')->form());

        self::assertResponseRedirects();
        $this->entityManager()->clear();
        $saved = $this->entityManager()->find(Page::class, $this->page->getId());
        self::assertSame(['fr'], $saved?->getTranslations()->map(static fn (PageTranslation $translation): ?string => $translation->getLanguage()?->getIso())->getValues());
    }

    /**
     * @param list<mixed>|string $content
     */
    private function save(array|string $content): Crawler
    {
        $crawler = $this->client->request('GET', $this->editUrl());
        $field = \sprintf('page[translations][%d][content]', $this->site->getDefaultLanguage()?->getId());

        return $this->client->submit($crawler->filter('form[name="page"]')->form(), [
            $field => \is_string($content) ? $content : json_encode($content, \JSON_THROW_ON_ERROR),
        ]);
    }

    private function editUrl(): string
    {
        return '/admin/pages/' . $this->page->getId() . '/edit';
    }

    private function translation(): PageTranslation
    {
        $page = $this->entityManager()->find(Page::class, $this->page->getId());
        $translation = $page?->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $translation);

        return $translation;
    }
}
