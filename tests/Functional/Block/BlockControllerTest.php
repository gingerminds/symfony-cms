<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Block;

use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\DomCrawler\Crawler;

final class BlockControllerTest extends ApiTestCase
{
    use BlockModalTrait;

    public function testThePageEditorsOnly(): void
    {
        $this->client->loginUser($this->fixtures->user('reader@example.com', ['view pages']), 'admin');
        $this->client->request('POST', self::BASE . 'title_text/form');
        self::assertResponseStatusCodeSame(403);
        $this->client->request('POST', self::BASE . 'title_text/validate');
        self::assertResponseStatusCodeSame(403);

        $this->client->loginUser($this->fixtures->user('editor@example.com', ['edit pages']), 'admin');
        $this->client->request('POST', self::BASE . 'unknown/form');
        self::assertResponseStatusCodeSame(404);
        $this->client->request('POST', self::BASE . 'title_text/form');
        self::assertResponseIsSuccessful();
    }

    public function testTheFormOfANewBlock(): void
    {
        $this->loginEditor();

        $json = $this->form('test_links');

        self::assertMatchesRegularExpression('/^[0-9a-f]{32}$/', $json['uid']);
        self::assertSame(['test_links', 'Liens', 'bi-link'], [$json['type'], $json['label'], $json['icon']]);
        $form = new Crawler($json['html']);
        self::assertSame('test_links', $form->filter('form')->attr('data-type'));
        self::assertSame(['Intitulé', 'Style', 'Mis en avant', 'Liens'], $form->filter('form > .row > div')->each(
            static fn (Crawler $field): string => trim($field->filter('label, legend')->first()->text()),
        ));
        self::assertSame(['col-md-6', 'col-md-6', 'col-md-6', 'col-md-12'], $form->filter('form > .row > div')->each(static fn (Crawler $field): string => (string) $field->attr('class')), 'The admin form theme.');
        self::assertSame(['' => '', 'light' => 'Clair', 'dark' => 'Sombre'], array_combine(
            $form->filter('select[name="data[style]"] option')->each(static fn (Crawler $option): string => (string) $option->attr('value')),
            $form->filter('select[name="data[style]"] option')->each(static fn (Crawler $option): string => $option->text()),
        ));
        self::assertSame('light', $form->filter('select[name="data[style]"] option[selected]')->attr('value'), 'The default value.');
        self::assertCount(0, $form->filter('[data-gm-cms-repeater-target="rows"] .gm-cms-repeater-row'));
        self::assertStringContainsString('Ajouter un lien', $form->filter('[data-action="gm-cms-repeater#add"]')->text());
        self::assertStringContainsString('name="data[links][__name__][url]"', $json['html'], 'The prototype of a row.');
        self::assertCount(1, $form->filter('input[name="data[_token]"]'));

        // A bundle block: its extension field after the title, its WYSIWYG preset.
        $form = new Crawler($this->form('title_text')['html']);
        self::assertSame(['data[title]', 'data[subtitle]', 'data[text]'], $form->filter('form > .row input[type="text"], form > .row textarea')->each(
            static fn (Crawler $input): string => (string) $input->attr('name'),
        ));
        self::assertStringContainsString('"table"', (string) $form->filter('[data-controller="gm-cms-wysiwyg"]')->attr('data-gm-cms-wysiwyg-extensions-value'));
    }

    public function testTheFormOfAnEditedBlock(): void
    {
        $this->loginEditor();

        $json = $this->form('test_links', [
            'uid' => 'block-1',
            'data' => json_encode(['heading' => 'Liens utiles', 'highlighted' => true, 'links' => [['label' => 'A', 'url' => '/a'], ['label' => 'B', 'url' => '/b']]]),
        ]);

        self::assertSame('block-1', $json['uid']);
        $form = new Crawler($json['html']);
        self::assertSame('block-1', $form->filter('input[name="uid"]')->attr('value'));
        self::assertSame('Liens utiles', $form->filter('input[name="data[heading]"]')->attr('value'));
        self::assertSame('checked', $form->filter('input[name="data[highlighted]"]')->attr('checked'));
        self::assertSame(['/a', '/b'], $form->filter('[data-gm-cms-repeater-target="rows"] input[name$="[url]"]')->each(static fn (Crawler $input): string => (string) $input->attr('value')));
        self::assertSame('2', $form->filter('[data-controller="gm-cms-repeater"]')->attr('data-gm-cms-repeater-index-value'));
        self::assertCount(2, $form->filter('[data-gm-cms-repeater-target="rows"] .gm-cms-repeater-row.is-collapsed'), 'The stored rows are collapsed.');
    }

    public function testAnInvalidBlockGivesItsFormWithItsErrors(): void
    {
        $this->loginEditor();

        $json = $this->validate('test_links', ['heading' => '', 'style' => 'other', 'links' => [['label' => '', 'url' => '/a']]]);

        self::assertResponseStatusCodeSame(422);
        $form = new Crawler($json['html']);
        self::assertStringContainsString('Cette valeur ne doit pas être vide.', $form->filter('input[name="data[heading]"]')->ancestors()->first()->text());
        self::assertStringContainsString('Le choix sélectionné est invalide.', $json['html']);
        $row = $form->filter('[data-gm-cms-repeater-target="rows"] .gm-cms-repeater-row');
        self::assertCount(1, $row);
        self::assertStringNotContainsString('is-collapsed', (string) $row->attr('class'), 'An invalid row is open.');
        self::assertSame('/a', $row->filter('input[name="data[links][0][url]"]')->attr('value'));

        $json = $this->validate('test_links', ['heading' => 'Liens']);
        self::assertResponseStatusCodeSame(422);
        self::assertStringContainsString('Cette collection doit contenir 1 élément ou plus.', $json['html']);
    }

    public function testAValidBlockGivesItsDataAndItsPreview(): void
    {
        $this->loginEditor();

        $json = $this->validate('test_links', [
            'heading' => 'Liens utiles',
            'style' => 'dark',
            'highlighted' => '1',
            // Display order after a drag & drop: the indexes are not.
            'links' => [5 => ['label' => 'B', 'url' => '/b'], 2 => ['label' => 'A', 'url' => '/a']],
        ], 'block-1');

        self::assertResponseIsSuccessful();
        self::assertSame(['block-1', 'test_links', 'Liens', 'bi-link'], [$json['uid'], $json['type'], $json['label'], $json['icon']]);
        self::assertSame([
            'heading' => 'Liens utiles',
            'style' => 'dark',
            'highlighted' => true,
            'links' => [['label' => 'B', 'url' => '/b'], ['label' => 'A', 'url' => '/a']],
        ], $json['data']);
        self::assertSame(['B: /b', 'A: /a'], new Crawler($json['preview'])->filter('li')->each(static fn (Crawler $item): string => $item->text()));

        $json = $this->validate('title_text', ['title' => 'Bonjour', 'subtitle' => 'Monde', 'text' => '<p onclick="x()">Texte</p><script>x()</script>']);
        self::assertSame(['title' => 'Bonjour', 'subtitle' => 'Monde', 'text' => '<p>Texte</p>'], $json['data']);
        self::assertSame('Bonjour', new Crawler($json['preview'])->filter('h3')->text());
    }
}
