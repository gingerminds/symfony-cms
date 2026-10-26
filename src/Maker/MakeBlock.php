<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Maker;

use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Exception\RuntimeCommandException;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Maker\AbstractMaker;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;

/**
 * make:gm:cms-block Name (Laravel make:cms-block): `App\Cms\Block\Name` (key: the snake case
 * name) and its preview `templates/cms/block/<key>/preview.html.twig`. Refuses an existing class
 * or a key already used by another block.
 */
final class MakeBlock extends AbstractMaker
{
    public function __construct(
        private readonly BlockRegistry $registry,
    ) {
    }

    public static function getCommandName(): string
    {
        return 'make:gm:cms-block';
    }

    public static function getCommandDescription(): string
    {
        return 'Create a Gingerminds CMS content block and its admin preview';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command->addArgument('name', InputArgument::OPTIONAL, 'Block class name (e.g. <fg=yellow>Quote</>)');
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $details = $generator->createClassNameDetails(Str::asClassName((string) $input->getArgument('name')), 'Cms\\Block\\');
        $key = Str::asSnakeCase($details->getShortName());
        $existing = $this->registry->find($key);

        if ($existing instanceof BlockInterface) {
            throw new RuntimeCommandException(\sprintf('The key "%s" is already used by the block "%s": choose another name.', $key, $existing::class));
        }

        $previewTemplate = 'cms/block/' . $key . '/preview.html.twig';
        $generator->generateClass($details->getFullName(), __DIR__ . '/skeleton/Block.tpl.php', [
            'key' => $key,
            'preview_template' => $previewTemplate,
        ]);
        $generator->generateTemplate($previewTemplate, __DIR__ . '/skeleton/block_preview.tpl.php', ['key' => $key]);
        $generator->writeChanges();

        $this->writeSuccessMessage($io);
        $io->text('Next:');
        $io->listing([
            \sprintf('Declare the fields in <fg=yellow>%s::getFields()</> and render them in the preview.', $details->getShortName()),
            \sprintf('Translate <comment>block.%1$s.label</comment> and <comment>block.%1$s.field.title</comment> in <fg=yellow>translations/messages.<locale>.yaml</>.', $key),
        ]);
    }
}
