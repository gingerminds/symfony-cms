<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * A YouTube video: its id or its URL (`embed_code`).
 */
class Video extends AbstractBlock
{
    public static function getKey(): string
    {
        return 'video';
    }

    public function getIcon(): string
    {
        return 'bi-play-circle';
    }

    public function getOrder(): int
    {
        return 200;
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title'),
            BlockField::text('embed_code')->required()->help('block.video.help.embed_code'),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '@GingermindsCms/block/video/preview.html.twig';
    }

    /**
     * The YouTube id of an embed code: the id itself, or found in a YouTube URL.
     */
    public static function youtubeId(?string $embedCode): ?string
    {
        $embedCode = trim((string) $embedCode);

        if (1 === preg_match('#(?:youtu\.be/|youtube(?:-nocookie)?\.com/(?:embed/|v/|shorts/|live/|watch\?(?:.*&)?v=))([\w-]{11})#', $embedCode, $matches)) {
            return $matches[1];
        }

        return 1 === preg_match('/^[\w-]{11}$/', $embedCode) ? $embedCode : null;
    }
}
