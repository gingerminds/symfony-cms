<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Maker;

use Composer\Autoload\ClassLoader;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Gingerminds\CmsBundle\Maker\MakeBlock;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Util\AutoloaderUtil;
use Symfony\Bundle\MakerBundle\Util\ComposerAutoloaderFinder;
use Symfony\Bundle\MakerBundle\Util\MakerFileLinkFormatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\Validator\Validation;

final class MakeBlockTest extends TestCase
{
    private string $directory;

    private string $namespace;

    protected function setUp(): void
    {
        $this->directory = sys_get_temp_dir() . '/gingerminds-cms-maker-' . bin2hex(random_bytes(4));
        new Filesystem()->mkdir($this->directory . '/src');

        // The MakerBundle resolves the path of the generated classes from the Composer autoloader.
        $this->namespace = 'MakerTest' . bin2hex(random_bytes(4));
        $this->loader()->addPsr4($this->namespace . '\\', $this->directory . '/src/');
    }

    protected function tearDown(): void
    {
        new Filesystem()->remove($this->directory);
    }

    public function testTheBlockAndItsPreviewAreGenerated(): void
    {
        $output = $this->make('Quote');

        $class = (string) file_get_contents($this->directory . '/src/Cms/Block/QuoteType.php');
        self::assertStringContainsString('namespace ' . $this->namespace . '\Cms\Block;', $class);
        self::assertStringContainsString('class QuoteType extends AbstractType', $class);
        self::assertStringContainsString("#[AsContentBlock(key: 'quote', preview: 'cms/block/quote/preview.html.twig'", $class);
        self::assertStringContainsString('{{ data.title }}', (string) file_get_contents($this->directory . '/templates/cms/block/quote/preview.html.twig'));
        self::assertStringContainsString('block.quote.label', $output);

        // The generated class is a valid block.
        require $this->directory . '/src/Cms/Block/QuoteType.php';
        /** @var class-string<FormTypeInterface<mixed>> $type */
        $type = $this->namespace . '\Cms\Block\QuoteType';
        self::assertSame('quote', BlockRegistry::attribute($type)->key);
        self::assertSame(['title'], array_keys(Forms::createFormFactoryBuilder()->addExtension(new ValidatorExtension(Validation::createValidator()))->getFormFactory()->create($type)->all()));
    }

    public function testAKeyAlreadyUsedIsRefused(): void
    {
        $this->expectException(RuntimeCommandException::class);
        $this->expectExceptionMessage(\sprintf('The key "title_text" is already used by the block "%s"', TitleTextType::class));

        $this->make('TitleText');
    }

    private function make(string $name): string
    {
        $maker = new MakeBlock(new BlockRegistry(new ServiceLocator(['title_text' => static fn (): TitleTextType => new TitleTextType()]), Forms::createFormFactory()));
        $command = new Command(MakeBlock::getCommandName());
        $maker->configureCommand($command, new InputConfiguration());
        $input = new ArrayInput(['name' => $name], $command->getDefinition());
        $output = new BufferedOutput();

        $fileManager = new FileManager(
            new Filesystem(),
            new AutoloaderUtil(new ComposerAutoloaderFinder($this->namespace)),
            new MakerFileLinkFormatter(),
            $this->directory,
            $this->directory . '/templates',
        );
        $maker->generate($input, new ConsoleStyle($input, $output), new Generator($fileManager, $this->namespace));

        return $output->fetch();
    }

    private function loader(): ClassLoader
    {
        foreach (spl_autoload_functions() as $function) {
            if (\is_array($function) && $function[0] instanceof ClassLoader) {
                return $function[0];
            }
        }

        self::fail('No Composer class loader.');
    }
}
