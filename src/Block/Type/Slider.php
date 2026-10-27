<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title and slides (an image each).
 */
class Slider extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'slider';
    }

    public function getIcon(): string
    {
        return 'bi-images';
    }

    public function getOrder(): int
    {
        return 350;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title'),
            BlockField::repeater('slides', [
                BlockField::file('image')->required(),
            ])->addLabel('block.slider.add')->itemLabel('block.slider.item'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/slider/preview.html.twig';
    }
}
