<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Validator\LinkUrl;

/**
 * A title and links (label, absolute or relative URL, image).
 */
class LinkList extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'link_list';
    }

    public function getIcon(): string
    {
        return 'bi-link-45deg';
    }

    public function getOrder(): int
    {
        return 400;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
            BlockField::repeater('links', [
                BlockField::text('label')->required(),
                BlockField::text('url')->required()->constraints(new LinkUrl()),
                BlockField::file('image'),
            ])->addLabel('block.link_list.add')->itemLabel('block.link_list.item'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/link_list/preview.html.twig';
    }
}
