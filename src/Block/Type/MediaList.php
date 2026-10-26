<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title and medias of the media library, with a subtitle each.
 */
class MediaList extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'media_list';
    }

    public function getIcon(): string
    {
        return 'bi-collection-play';
    }

    public function getOrder(): int
    {
        return 450;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title'),
            BlockField::repeater('items', [
                BlockField::media('media')->required(),
                BlockField::text('subtitle'),
            ])->addLabel('block.media_list.add')->itemLabel('block.media_list.item'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/media_list/preview.html.twig';
    }
}
