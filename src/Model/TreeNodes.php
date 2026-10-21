<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

/**
 * Builds the admin views of a tree from its nodes, loaded at once and ordered within each level.
 */
final class TreeNodes
{
    /**
     * The nodes grouped by parent id, the roots under 0.
     *
     * @template T of TreeNodeInterface
     *
     * @param iterable<T> $nodes
     *
     * @return array<int, list<T>>
     */
    public static function groupByParent(iterable $nodes): array
    {
        $children = [];

        foreach ($nodes as $node) {
            $children[$node->getParent()?->getId() ?? 0][] = $node;
        }

        return $children;
    }

    /**
     * The tree depth-first, each node with its depth.
     *
     * @template T of TreeNodeInterface
     *
     * @param iterable<T> $nodes
     *
     * @return list<array{T, int}>
     */
    public static function flatten(iterable $nodes): array
    {
        $children = self::groupByParent($nodes);
        $flatten = static function (int $parentId, int $depth) use (&$flatten, $children): iterable {
            foreach ($children[$parentId] ?? [] as $node) {
                yield [$node, $depth];
                yield from $flatten((int) $node->getId(), $depth + 1);
            }
        };

        return iterator_to_array($flatten(0, 0), false);
    }

    /**
     * The possible parents of `$node`: every node but itself and its descendants, with their depth
     * (to indent the choice labels).
     *
     * @template T of TreeNodeInterface
     *
     * @param iterable<T> $nodes
     *
     * @return array{list<T>, array<int, int>} the choices and their depth by id
     */
    public static function parentChoices(iterable $nodes, ?TreeNodeInterface $node): array
    {
        $choices = [];
        $depths = [];
        $excludedDepth = null;

        foreach (self::flatten($nodes) as [$choice, $depth]) {
            // The descendants of the node follow it, deeper.
            if (null !== $excludedDepth && $depth > $excludedDepth) {
                continue;
            }

            $excludedDepth = null;

            if (null !== $node?->getId() && $choice->getId() === $node->getId()) {
                $excludedDepth = $depth;

                continue;
            }

            $choices[] = $choice;
            $depths[(int) $choice->getId()] = $depth;
        }

        return [$choices, $depths];
    }
}
