<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Twig;

use Twig\Attribute\AsTwigFunction;

/**
 * Twig helpers of the publication workflow.
 */
final readonly class PublicationExtension
{
    /**
     * @param array<string, string> $statusColors
     */
    public function __construct(
        private array $statusColors,
    ) {
    }

    /**
     * The Bootstrap color of a publication status badge (`gingerminds_cms.publication.status_colors`,
     * `secondary` otherwise).
     */
    #[AsTwigFunction('gm_cms_status_color')]
    public function statusColor(string $status): string
    {
        return $this->statusColors[$status] ?? 'secondary';
    }
}
