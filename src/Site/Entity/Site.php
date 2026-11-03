<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Site\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\SiteMainMenuInterface;
use Gingerminds\CmsBundle\Model\SiteMainMenuTrait;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/**
 * The multisite Site with a main menu, mapped only when declared as `gingerminds_multisite.resources.site.entity`
 * (outside src/Entity: Doctrine does not discover it otherwise). It inherits the multisite `#[ApiResource]`.
 */
#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
class Site extends BaseSite implements SiteMainMenuInterface
{
    use SiteMainMenuTrait;
}
