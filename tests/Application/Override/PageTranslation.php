<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Override;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Entity\Page\BasePageTranslation;

/**
 * `test_override` environment: a project page translation with a translated field of its own.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_translations')]
#[ORM\UniqueConstraint(name: 'page_translations_page_id_language_id_unique', columns: ['page_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
class PageTranslation extends BasePageTranslation
{
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $subtitle = null;

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function setSubtitle(?string $subtitle): void
    {
        $this->subtitle = $subtitle;
    }
}
