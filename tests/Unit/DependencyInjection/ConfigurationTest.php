<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\DependencyInjection;

use Gingerminds\CmsBundle\GingermindsCmsBundle;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ConfigurationTest extends TestCase
{
    public function testDefaults(): void
    {
        $config = $this->process([]);

        self::assertSame(['default', 'extended', 'minimal', 'full'], array_keys($config['wysiwyg']['presets']));
        self::assertSame(['bold', 'italic', 'underline', 'link'], $config['wysiwyg']['presets']['minimal']['extensions']);
        self::assertSame(['create' => true, 'edit' => false], $config['slug_overwrite']);
        self::assertSame(['disabled' => [], 'order' => []], $config['blocks']);
        self::assertSame(['entity' => null, 'controller' => null, 'form' => null], $config['resources']['menu']);
        self::assertSame(['entity' => null, 'controller' => null, 'form' => null], $config['resources']['menu_item']);
    }

    public function testCustomPresetsReplaceTheDefaults(): void
    {
        $config = $this->process(['wysiwyg' => ['presets' => [
            'default' => ['extensions' => ['bold']],
            'news' => ['extensions' => ['bold', 'heading', 'table']],
        ]]]);

        self::assertSame(['default', 'news'], array_keys($config['wysiwyg']['presets']));
        self::assertSame(['bold', 'heading', 'table'], $config['wysiwyg']['presets']['news']['extensions']);
    }

    public function testBlocksCanBeDisabledAndReordered(): void
    {
        $config = $this->process(['blocks' => ['disabled' => ['video'], 'order' => ['faq' => 10, 'title_text' => 200, 'project-block' => 5]]]);

        self::assertSame(['video'], $config['blocks']['disabled']);
        self::assertSame(['faq' => 10, 'title_text' => 200, 'project-block' => 5], $config['blocks']['order']);
    }

    /**
     * @return iterable<string, array{array<string, mixed>}>
     */
    public static function invalidConfigurations(): iterable
    {
        yield 'unknown wysiwyg extension' => [['wysiwyg' => ['presets' => ['default' => ['extensions' => ['image']]]]]];
        yield 'preset without extension' => [['wysiwyg' => ['presets' => ['default' => ['extensions' => []]]]]];
        yield 'no default preset' => [['wysiwyg' => ['presets' => ['news' => ['extensions' => ['bold']]]]]];
        yield 'non integer block weight' => [['blocks' => ['order' => ['faq' => 'first']]]];
    }

    /**
     * @param array<string, mixed> $config
     */
    #[DataProvider('invalidConfigurations')]
    public function testInvalidConfigurationIsRejected(array $config): void
    {
        $this->expectException(InvalidConfigurationException::class);

        $this->process($config);
    }

    /**
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     */
    private function process(array $config): array
    {
        $container = new ContainerBuilder();
        $configuration = new GingermindsCmsBundle()->getContainerExtension()?->getConfiguration([], $container);
        self::assertInstanceOf(ConfigurationInterface::class, $configuration);

        return new Processor()->processConfiguration($configuration, [$config]);
    }
}
