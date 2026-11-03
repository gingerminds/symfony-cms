<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Symfony\Component\Form\AbstractType;

/**
 * Project override of a bundle block, its service replaced: same key and fields (its parent type,
 * with its extensions), its own icon.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(key: 'title_text', preview: '@GingermindsCms/block/title_text/preview.html.twig', icon: 'bi-fonts', order: 0, translationDomain: GingermindsCmsBundle::TRANSLATION_DOMAIN)]
final class TitleTextOverride extends AbstractType
{
    public function getParent(): string
    {
        return TitleTextType::class;
    }
}
