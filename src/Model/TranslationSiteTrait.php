<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * `site_id` of a translation, a copy of the one of its owner (as Laravel). The `$site` column is
 * mapped by the translation (nullable or not, as its Laravel table).
 */
trait TranslationSiteTrait
{
    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    /**
     * The owner, created after its translations in a form, gives them its site.
     */
    #[ORM\PrePersist]
    public function copySiteOfTheTranslatable(): void
    {
        $this->site = $this->translatable?->getSite() ?? $this->site;
    }
}
