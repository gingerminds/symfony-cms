<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Site\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\SiteMainMenuInterface;
use Gingerminds\CmsBundle\Model\SiteMainMenuTrait;
use Gingerminds\MultisiteBundle\Entity\Site\BaseSite;
use Gingerminds\MultisiteBundle\Repository\Site\SiteRepository;

/**
 * The multisite Site with a main menu, mapped only when declared as `gingerminds_multisite.resources.site.entity`
 * (outside src/Entity: neither Doctrine nor API Platform discover it otherwise).
 */
#[ORM\Entity(repositoryClass: SiteRepository::class)]
#[ORM\Table(name: 'sites')]
#[ApiResource(
    shortName: 'Site',
    operations: [
        new GetCollection(normalizationContext: ['groups' => [BaseSite::GROUP_LIST], 'skip_null_values' => false]),
    ],
    paginationClientItemsPerPage: true,
    provider: 'gingerminds_multisite.api.provider.site',
)]
class Site extends BaseSite implements SiteMainMenuInterface
{
    use SiteMainMenuTrait;
}
