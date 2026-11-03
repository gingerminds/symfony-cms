<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;

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
     * The owner gives its site to a new translation (created before or after it).
     */
    #[ORM\PrePersist]
    public function copySiteOfTheTranslatable(): void
    {
        $owner = $this->getTranslatable();
        $this->site = ($owner instanceof SiteContextedInterface ? $owner->getSite() : null) ?? $this->site;
    }
}
