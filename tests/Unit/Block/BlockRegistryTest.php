<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\ContentBlock;
use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Gingerminds\CmsBundle\Tests\Application\Block\LinksBlockType;
use Gingerminds\CmsBundle\Tests\Application\Block\TitleTextOverride;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Forms;

final class BlockRegistryTest extends TestCase
{
    public function testThePickerListsTheEnabledBlocksByWeight(): void
    {
        self::assertSame(['title_text', 'test_links'], $this->keys($this->registry()->active()), 'TitleText 0, LinksBlock 5.');
        self::assertSame(['test_links', 'title_text'], $this->keys($this->registry(order: ['test_links' => -1])->active()));
        self::assertSame(['test_links', 'title_text'], $this->keys($this->registry(order: ['title_text' => 5])->active()), 'Same weight: by key.');

        $registry = $this->registry(disabled: ['title_text']);
        self::assertSame(['test_links'], $this->keys($registry->active()));
        // A disabled block still reads the pages using it.
        self::assertSame(TitleTextType::class, $registry->find('title_text')?->formType);
        self::assertNull($registry->find('unknown'));
    }

    public function testTheMetadataOfABlock(): void
    {
        $titleText = $this->registry()->find('title_text');

        self::assertEquals(new ContentBlock('title_text', TitleTextType::class, '@GingermindsCms/block/title_text/preview.html.twig', 'bi-type', 0, null, 'GingermindsCms'), $titleText);
        self::assertSame('block.title_text.label', $titleText?->label);
        self::assertSame('block.title_text.field.%name%', $titleText?->fieldLabelFormat());
        self::assertSame('Liens', $this->registry()->find('test_links')?->label);
    }

    public function testTwoBlocksCannotShareAKey(): void
    {
        $registry = new BlockRegistry(new ServiceLocator([
            'a' => static fn (): TitleTextType => new TitleTextType(),
            'b' => static fn (): TitleTextOverride => new TitleTextOverride(),
        ]), Forms::createFormFactory());

        $this->expectExceptionMessage('have the same key "title_text"');
        $registry->all();
    }

    /**
     * @param list<string>       $disabled
     * @param array<string, int> $order
     */
    private function registry(array $disabled = [], array $order = []): BlockRegistry
    {
        return new BlockRegistry(
            new ServiceLocator([
                'gingerminds_cms.block.title_text' => static fn (): TitleTextType => new TitleTextType(),
                LinksBlockType::class => static fn (): LinksBlockType => new LinksBlockType(),
            ]),
            Forms::createFormFactory(),
            $disabled,
            $order,
        );
    }

    /**
     * @param list<ContentBlock> $blocks
     *
     * @return list<string>
     */
    private function keys(array $blocks): array
    {
        return array_map(static fn (ContentBlock $block): string => $block->key, $blocks);
    }
}
