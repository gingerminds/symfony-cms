<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'pages')]
#[ORM\UniqueConstraint(name: 'pages_code_site_id_unique', columns: ['code', 'site_id'])]
class Page extends BasePage
{
}
