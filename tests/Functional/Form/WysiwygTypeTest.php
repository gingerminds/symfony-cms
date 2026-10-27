<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Form;

use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Twig\Environment;

final class WysiwygTypeTest extends ApiTestCase
{
    public function testTheEditorOfAPreset(): void
    {
        $form = $this->form(['preset' => 'minimal', 'rows' => 8, 'required' => true]);
        $form->setData(['text' => '<p>Bonjour</p>']);

        $crawler = new Crawler(self::getContainer()->get(Environment::class)->createTemplate('{{ form_widget(form.text) }}')->render(['form' => $form->createView()]));
        $editor = $crawler->filter('[data-controller="gm-cms-wysiwyg"]');

        self::assertSame('["bold","italic","underline","link"]', $editor->attr('data-gm-cms-wysiwyg-extensions-value'));
        self::assertSame('8', $editor->attr('data-gm-cms-wysiwyg-rows-value'));
        self::assertSame('Gras', json_decode((string) $editor->attr('data-gm-cms-wysiwyg-labels-value'), true, flags: \JSON_THROW_ON_ERROR)['bold']);
        $textarea = $editor->filter('textarea[data-gm-cms-wysiwyg-target="input"]');
        self::assertSame('<p>Bonjour</p>', $textarea->text());
        self::assertNotNull($textarea->attr('hidden'));
        self::assertNull($textarea->attr('required'), 'A hidden field cannot be required for the browser.');
    }

    public function testAnUnknownPresetIsRejected(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('"default", "extended", "minimal", "full"');

        $this->form(['preset' => 'unknown']);
    }

    /**
     * @return iterable<string, array{string, ?string}>
     */
    public static function submittedHtml(): iterable
    {
        yield 'kept' => ['<h2>Titre</h2><p><strong>a</strong> <em>b</em> <u>c</u> <s>d</s></p><ul><li><p>e</p></li></ul><blockquote><p>f</p></blockquote><hr />', null];
        yield 'link' => ['<p><a href="https://example.com" target="_blank" rel="noopener noreferrer nofollow">lien</a> <a href="/contact">contact</a> <a href="tel:0100000000">tel</a></p>', null];
        yield 'table' => ['<table><tbody><tr><th colspan="1" rowspan="1">a</th></tr><tr><td colspan="1" rowspan="1">b</td></tr></tbody></table>', null];
        yield 'script' => ['<p>a</p><script>alert(1)</script>', '<p>a</p>'];
        yield 'event attribute' => ['<p onclick="alert(1)" style="color:red">a</p>', '<p>a</p>'];
        yield 'javascript link' => ['<p><a href="javascript:alert(1)">a</a></p>', '<p><a>a</a></p>'];
        yield 'unknown element' => ['<div><p>a</p><img src="x" onerror="alert(1)" /><iframe src="https://example.com"></iframe></div>', '<p>a</p>'];
        yield 'empty paragraph' => ['<p></p>', ''];
        yield 'blank paragraphs' => ['<p> &nbsp;</p><p><br /></p>', ''];
    }

    #[DataProvider('submittedHtml')]
    public function testTheSubmittedHtmlIsSanitized(string $html, ?string $expected): void
    {
        $form = $this->form();
        $form->submit(['text' => $html]);

        self::assertTrue($form->isValid());
        self::assertSame('' === $expected ? null : ($expected ?? $html), $form->get('text')->getData());
    }

    /**
     * @param array<string, mixed> $options
     *
     * @return FormInterface<array<string, mixed>|null>
     */
    private function form(array $options = []): FormInterface
    {
        return self::getContainer()->get(FormFactoryInterface::class)
            ->createBuilder(FormType::class, null, ['csrf_protection' => false])
            ->add('text', WysiwygType::class, $options)
            ->getForm();
    }
}
