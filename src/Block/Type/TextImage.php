<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title, a rich text and an image beside it (left, or right with `image_position`).
 */
class TextImage extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'text_image';
    }

    public function getIcon(): string
    {
        return 'bi-image';
    }

    public function getOrder(): int
    {
        return 100;
    }

    public function getFields(): array
    {
        return [
            BlockField::toggle('image_position')->help('block.text_image.help.image_position'),
            BlockField::text('title')->required(),
            BlockField::wysiwyg('text'),
            BlockField::file('image')->required()->size('xl'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/text_image/preview.html.twig';
    }
}
