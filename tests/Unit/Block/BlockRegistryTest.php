<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Type\TitleText;
use Gingerminds\CmsBundle\Tests\Application\Block\LinksBlock;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ServiceLocator;

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
        self::assertInstanceOf(TitleText::class, $registry->find('title_text'));
        self::assertNull($registry->find('unknown'));
    }

    public function testDefaultsAndLabels(): void
    {
        $registry = $this->registry();
        $links = new LinksBlock();
        $titleText = new TitleText();

        self::assertSame(['heading' => null, 'style' => 'light', 'highlighted' => false, 'links' => []], $registry->getDefaults($links));
        self::assertSame('block.title_text.field.title', $registry->getFieldLabel($titleText, BlockField::text('title')));
        self::assertSame('block.title_text.field.links_url', $registry->getFieldLabel($titleText, BlockField::text('url'), BlockField::repeater('links', [])));
        self::assertSame('Custom', $registry->getFieldLabel($titleText, BlockField::text('title')->label('Custom')));
        self::assertSame('block.title_text.label', $titleText->getLabel());
    }

    public function testARepeaterCannotHoldARepeater(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        BlockField::repeater('rows', [BlockField::repeater('cells', [])]);
    }

    /**
     * @param list<string>       $disabled
     * @param array<string, int> $order
     */
    private function registry(array $disabled = [], array $order = []): BlockRegistry
    {
        return new BlockRegistry(
            new ServiceLocator([
                'test_links' => static fn (): BlockInterface => new LinksBlock(),
                'title_text' => static fn (): BlockInterface => new TitleText(),
            ]),
            new ServiceLocator([]),
            [],
            $disabled,
            $order,
        );
    }

    /**
     * @param list<BlockInterface> $blocks
     *
     * @return list<string>
     */
    private function keys(array $blocks): array
    {
        return array_map(static fn (BlockInterface $block): string => $block::getKey(), $blocks);
    }
}
