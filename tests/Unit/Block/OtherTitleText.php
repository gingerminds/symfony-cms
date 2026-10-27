<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Block;

use Gingerminds\CmsBundle\Block\AbstractBlock;

/**
 * A block unrelated to TitleText declaring its key (BlockPassTest).
 */
final class OtherTitleText extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'title_text';
    }

    public function getIcon(): string
    {
        return 'bi-x';
    }

    public function getFields(): array
    {
        return [];
    }

    public function getPreviewTemplate(): string
    {
        return 'other.html.twig';
    }
}
