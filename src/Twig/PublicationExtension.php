<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * `gm_cms_status_color(status)`: the Bootstrap color of a publication status badge
 * (`gingerminds_cms.publication.status_colors`, `secondary` otherwise).
 */
final class PublicationExtension extends AbstractExtension
{
    /**
     * @param array<string, string> $statusColors
     */
    public function __construct(
        private readonly array $statusColors,
    ) {
    }

    public function getFunctions(): array
    {
        return [new TwigFunction('gm_cms_status_color', fn (string $status): string => $this->statusColors[$status] ?? 'secondary')];
    }
}
