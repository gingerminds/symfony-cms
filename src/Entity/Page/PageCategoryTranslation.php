<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;

/**
 * The bundle PageCategoryTranslation (`resources.page_category_translation.entity` replaces it): a project
 * entity extends BasePageCategoryTranslation and restates these class-level attributes.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_category_translations')]
#[ORM\UniqueConstraint(name: 'page_category_translations_page_category_id_language_id_unique', columns: ['page_category_id', 'language_id'])]
#[ORM\HasLifecycleCallbacks]
class PageCategoryTranslation extends BasePageCategoryTranslation
{
}
