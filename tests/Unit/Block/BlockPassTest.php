<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\DuplicateBlockKeyException;
use Gingerminds\CmsBundle\Block\Type\TitleText;
use Gingerminds\CmsBundle\DependencyInjection\Compiler\BlockPass;
use Gingerminds\CmsBundle\Tests\Application\Block\LinksBlock;
use Gingerminds\CmsBundle\Tests\Application\Block\SubtitleExtension;
use Gingerminds\CmsBundle\Tests\Application\Block\TitleTextOverride;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;

final class BlockPassTest extends TestCase
{
    /**
     * @return iterable<string, array{list<class-string<BlockInterface>>}>
     */
    public static function overrideOrders(): iterable
    {
        yield 'bundle block first' => [[TitleText::class, TitleTextOverride::class]];
        yield 'override first' => [[TitleTextOverride::class, TitleText::class]];
    }

    /**
     * @param list<class-string<BlockInterface>> $classes
     */
    #[DataProvider('overrideOrders')]
    public function testABlockExtendingAnotherOneReplacesIt(array $classes): void
    {
        $container = $this->container();

        foreach ([...$classes, LinksBlock::class] as $index => $class) {
            $container->register('block_' . $index, $class)->setPublic(true)->addTag(BlockInterface::TAG);
        }

        $registry = $this->compile($container);

        self::assertSame(['title_text', 'test_links'], array_keys($registry->all()));
        self::assertInstanceOf(TitleTextOverride::class, $registry->find('title_text'));
    }

    public function testTwoUnrelatedBlocksCannotShareAKey(): void
    {
        $container = $this->container();
        $container->register('title_text', TitleText::class)->addTag(BlockInterface::TAG);
        $container->register('other', OtherTitleText::class)->addTag(BlockInterface::TAG);

        $this->expectException(DuplicateBlockKeyException::class);
        $this->expectExceptionMessage(\sprintf('The blocks "%s" and "%s" both declare the key "title_text"', TitleText::class, OtherTitleText::class));
        $this->compile($container);
    }

    public function testExtensionsAreAppliedByPriority(): void
    {
        $container = $this->container();
        $container->register('title_text', TitleText::class)->addTag(BlockInterface::TAG);
        $container->register('subtitle', SubtitleExtension::class)->addTag(BlockExtensionInterface::TAG);

        $registry = $this->compile($container);
        $block = $registry->find('title_text');

        self::assertNotNull($block);
        self::assertSame(['title', 'subtitle', 'text'], array_column($registry->getFields($block), 'name'));
    }

    public function testAnExtensionOfAnUnknownBlockFails(): void
    {
        $container = $this->container();
        $container->register('extension', UnknownBlockExtension::class)->addTag(BlockExtensionInterface::TAG);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('extends the unknown block "unknown"');
        $this->compile($container);
    }

    private function container(): ContainerBuilder
    {
        $container = new ContainerBuilder();
        $container->register(BlockPass::REGISTRY, BlockRegistry::class)->setPublic(true)->setArguments([null, null, [], [], []]);
        $container->addCompilerPass(new BlockPass());

        return $container;
    }

    private function compile(ContainerBuilder $container): BlockRegistry
    {
        $container->compile();
        $registry = $container->get(BlockPass::REGISTRY);
        self::assertInstanceOf(BlockRegistry::class, $registry);

        return $registry;
    }
}
