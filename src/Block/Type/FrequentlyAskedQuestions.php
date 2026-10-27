<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A title and questions with their answer.
 */
class FrequentlyAskedQuestions extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'faq';
    }

    public function getIcon(): string
    {
        return 'bi-question-lg';
    }

    public function getOrder(): int
    {
        return 500;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
            BlockField::repeater('items', [
                BlockField::text('question')->required()->size('xl'),
                BlockField::wysiwyg('answer')->required(),
            ])->addLabel('block.faq.add')->itemLabel('block.faq.item'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/faq/preview.html.twig';
    }
}
