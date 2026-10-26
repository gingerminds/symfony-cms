<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\Type\TitleText;

/**
 * Project override of a bundle block: same key, its own icon.
 */
final class TitleTextOverride extends TitleText
{
    public function getIcon(): string
    {
        return 'bi-fonts';
    }
}
