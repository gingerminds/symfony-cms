<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Override;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Entity\Page\BasePage;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

/**
 * `test_override` environment: a project page (the bundle #[ApiResource] is inherited).
 */
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'pages')]
#[ORM\UniqueConstraint(name: 'pages_code_site_id_unique', columns: ['code', 'site_id'])]
class Page extends BasePage
{
}
