<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Model;

use Gingerminds\CmsBundle\Model\TreeNodeInterface;
use Gingerminds\CmsBundle\Model\TreeNodes;
use PHPUnit\Framework\TestCase;

final class TreeNodesTest extends TestCase
{
    public function testGroupFlattenAndParentChoices(): void
    {
        $products = $this->node(1);
        $tractors = $this->node(2, $products);
        $mowers = $this->node(3, $tractors);
        $news = $this->node(4);
        $nodes = [$products, $news, $tractors, $mowers];

        self::assertSame([0 => [$products, $news], 1 => [$tractors], 2 => [$mowers]], TreeNodes::groupByParent($nodes));
        self::assertSame([[$products, 0], [$tractors, 1], [$mowers, 2], [$news, 0]], TreeNodes::flatten($nodes));

        // A new node: every node; "tractors": neither itself nor "mowers".
        self::assertSame([[$products, $tractors, $mowers, $news], [1 => 0, 2 => 1, 3 => 2, 4 => 0]], TreeNodes::parentChoices($nodes, null));
        self::assertSame([[$products, $news], [1 => 0, 4 => 0]], TreeNodes::parentChoices($nodes, $tractors));
    }

    private function node(int $id, ?TreeNodeInterface $parent = null): TreeNodeInterface
    {
        return new readonly class($id, $parent) implements TreeNodeInterface {
            public function __construct(
                private int $id,
                private ?TreeNodeInterface $parent,
            ) {
            }

            public function getId(): int
            {
                return $this->id;
            }

            public function getParent(): ?TreeNodeInterface
            {
                return $this->parent;
            }
        };
    }
}
