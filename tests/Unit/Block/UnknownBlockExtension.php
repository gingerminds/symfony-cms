<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockInterface;

/**
 * An extension of a block that does not exist (BlockPassTest).
 */
final class UnknownBlockExtension implements BlockExtensionInterface
{
    public static function getExtendedBlocks(): iterable
    {
        return ['unknown'];
    }

    public function buildFields(array $fields, BlockInterface $block): array
    {
        return $fields;
    }
}
