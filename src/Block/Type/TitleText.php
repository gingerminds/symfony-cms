<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title and a rich text (tables allowed).
 */
class TitleText extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'title_text';
    }

    public function getIcon(): string
    {
        return 'bi-type';
    }

    public function getOrder(): int
    {
        return 0;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
            BlockField::wysiwyg('text')->preset('extended'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/title_text/preview.html.twig';
    }
}
