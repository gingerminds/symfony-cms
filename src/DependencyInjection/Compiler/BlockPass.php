<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\DependencyInjection\Compiler;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\DuplicateBlockKeyException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Compiler\ServiceLocatorTagPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Indexes the `gingerminds_cms.block` services by key for BlockRegistry: two blocks with the same
 * key fail, unless one extends the other (it replaces it). Maps the `gingerminds_cms.block_extension`
 * services to the blocks they extend.
 */
final class BlockPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const string REGISTRY = 'gingerminds_cms.block.registry';

    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(self::REGISTRY)) {
            return;
        }

        /** @var array<string, array{Reference, class-string<BlockInterface>}> $blocks */
        $blocks = [];

        foreach (array_keys($container->findTaggedServiceIds(BlockInterface::TAG)) as $id) {
            /** @var class-string<BlockInterface> $class */
            $class = $this->classOf($container, $id, BlockInterface::class);
            $key = $class::getKey();
            $other = $blocks[$key][1] ?? null;

            if (null !== $other && ($class === $other || is_subclass_of($other, $class))) {
                continue;
            }

            if (null !== $other && !is_subclass_of($class, $other)) {
                throw DuplicateBlockKeyException::forKey($key, $other, $class);
            }

            $blocks[$key] = [new Reference($id), $class];
        }

        $extensions = [];
        $extensionIds = [];

        foreach ($this->findAndSortTaggedServices(BlockExtensionInterface::TAG, $container) as $reference) {
            $id = (string) $reference;
            /** @var class-string<BlockExtensionInterface> $class */
            $class = $this->classOf($container, $id, BlockExtensionInterface::class);

            foreach ($class::getExtendedBlocks() as $key) {
                if (!isset($blocks[$key])) {
                    throw new InvalidArgumentException(\sprintf('The block extension "%s" extends the unknown block "%s".', $class, $key));
                }

                $extensions[$id] = $reference;
                $extensionIds[$key][] = $id;
            }
        }

        $container->getDefinition(self::REGISTRY)
            ->setArgument(0, ServiceLocatorTagPass::register($container, array_map(static fn (array $block): Reference => $block[0], $blocks)))
            ->setArgument(1, ServiceLocatorTagPass::register($container, $extensions))
            ->setArgument(2, $extensionIds);
    }

    /**
     * @param class-string $interface
     *
     * @return class-string
     */
    private function classOf(ContainerBuilder $container, string $id, string $interface): string
    {
        $class = $container->getParameterBag()->resolveValue($container->findDefinition($id)->getClass());

        if (!\is_string($class) || !is_subclass_of($class, $interface)) {
            throw new InvalidArgumentException(\sprintf('The service "%s" must implement "%s".', $id, $interface));
        }

        return $class;
    }
}
