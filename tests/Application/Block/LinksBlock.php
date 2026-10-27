<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * Project block of the tests: every field type (select, switch, repeater).
 */
final class LinksBlock extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'test_links';
    }

    public function getLabel(): string
    {
        return 'Liens';
    }

    public function getIcon(): string
    {
        return 'bi-link';
    }

    public function getOrder(): int
    {
        return 5;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('heading')->required()->label('Intitulé'),
            BlockField::select('style', ['light' => 'Clair', 'dark' => 'Sombre'])->default('light')->label('Style'),
            BlockField::toggle('highlighted')->label('Mis en avant'),
            BlockField::repeater('links', [
                BlockField::text('label')->required()->label('Libellé'),
                BlockField::text('url')->required()->label('URL'),
            ])->required()->label('Liens')->addLabel('Ajouter un lien')->itemLabel('Lien'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return 'block/test_links.html.twig';
    }

    public function getTranslationDomain(): string
    {
        return 'messages';
    }
}
