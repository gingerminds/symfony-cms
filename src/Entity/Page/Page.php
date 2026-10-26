<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;

/**
 * Public API of the current site, in the current language (Laravel URIs): the published pages
 * (filters and facets), a page by id whatever its status (front preview), a published page by
 * URL path or by code.
 */
#[ApiResource(
    shortName: 'Page',
    operations: [
        new GetCollection(normalizationContext: ['groups' => [BasePage::GROUP_LIST], 'skip_null_values' => false]),
        new Get(),
        new Get(
            uriTemplate: '/pages/by-slug/{slug}',
            uriVariables: ['slug' => new Link(fromClass: self::class, identifiers: ['slug'])],
            requirements: ['slug' => '.+'],
            name: 'gingerminds_cms_page_by_path',
        ),
    ],
    normalizationContext: ['groups' => [BasePage::GROUP_READ], 'skip_null_values' => false],
    paginationClientItemsPerPage: true,
    provider: 'gingerminds_cms.api.provider.page',
)]
#[ORM\Entity(repositoryClass: PageRepository::class)]
#[ORM\Table(name: 'pages')]
#[ORM\UniqueConstraint(name: 'pages_code_site_id_unique', columns: ['code', 'site_id'])]
class Page extends BasePage
{
}
