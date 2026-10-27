<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\BlockExtensionInterface;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\Type\TitleText;

/**
 * Project extension of a bundle block: a subtitle after the title.
 */
final class SubtitleExtension implements BlockExtensionInterface
{
    public static function getExtendedBlocks(): iterable
    {
        return [TitleText::getKey()];
    }

    public function buildFields(array $fields, BlockInterface $block): array
    {
        return [$fields[0], BlockField::text('subtitle')->label('Sous-titre'), ...\array_slice($fields, 1)];
    }
}
