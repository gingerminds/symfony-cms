<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Twig;

use Gingerminds\CmsBundle\Block\Type\VideoType;
use Twig\Attribute\AsTwigFunction;

/**
 * Twig helpers of the block previews.
 */
final class BlockExtension
{
    /**
     * The YouTube id of a video block embed code (VideoType::youtubeId()).
     */
    #[AsTwigFunction('gm_cms_youtube_id')]
    public static function youtubeId(?string $embedCode): ?string
    {
        return VideoType::youtubeId($embedCode);
    }
}
