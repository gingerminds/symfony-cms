<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;

/**
 * The bundle PageTranslation (`resources.page_translation.entity` replaces it): a project
 * entity extends BasePageTranslation and restates these class-level attributes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_translations')]
#[ORM\UniqueConstraint(name: 'page_translations_page_id_language_id_unique', columns: ['page_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
class PageTranslation extends BasePageTranslation
{
}
