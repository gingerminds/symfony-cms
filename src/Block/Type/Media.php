<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title and a file of any type (document, video...).
 */
class Media extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'media';
    }

    public function getIcon(): string
    {
        return 'bi-file-earmark-richtext';
    }

    public function getOrder(): int
    {
        return 150;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
            BlockField::file('file')->required()->accept([])->size('xl'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/media/preview.html.twig';
    }
}
