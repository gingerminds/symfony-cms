<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title, a text and cards (title, description, image).
 */
class Cards extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'cards';
    }

    public function getIcon(): string
    {
        return 'bi-grid-3x2-gap';
    }

    public function getOrder(): int
    {
        return 300;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
            BlockField::wysiwyg('text')->rows(6),
            BlockField::repeater('cards', [
                BlockField::text('title'),
                BlockField::textarea('description'),
                BlockField::file('image')->required(),
            ])->addLabel('block.cards.add')->itemLabel('block.cards.item'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/cards/preview.html.twig';
    }
}
