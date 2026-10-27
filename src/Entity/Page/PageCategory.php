<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;

#[ORM\Entity(repositoryClass: PageCategoryRepository::class)]
#[ORM\Table(name: 'page_categories')]
#[ORM\UniqueConstraint(name: 'page_categories_code_site_id_unique', columns: ['code', 'site_id'])]
class PageCategory extends BasePageCategory
{
}
