<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Api;

use ApiPlatform\Metadata\ApiProperty;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\SerializedName;

/**
 * A hit of the public search, whatever the engine (SearchProvider):
 * - `/search-indices`: the lean hits (autocomplete);
 * - `/search-results`: each hit with its content (`resource`, its own list serialization group) and
 *   the `type` facet (types and their categories).
 * `filters[search]`, `filters[type][]=page|page:12`, `page`, `itemsPerPage`; the current site and
 * language (the fallback one with `translation_fallback`).
 */
#[ApiResource(shortName: 'Search', operations: [
    new GetCollection(
        uriTemplate: '/search-indices',
        normalizationContext: ['groups' => [self::GROUP_LIST]],
        name: 'gingerminds_cms_search_indices',
    ),
    new GetCollection(
        uriTemplate: '/search-results',
        normalizationContext: ['groups' => [self::GROUP_LIST, self::GROUP_RESULT]],
        name: 'gingerminds_cms_search_results',
        extraProperties: [self::WITH_RESOURCES => true],
    ),
], paginationClientItemsPerPage: true, provider: 'gingerminds_cms.api.provider.search')]
final class SearchItem
{
    public const string GROUP_LIST = 'search:list';
    public const string GROUP_RESULT = 'search:result';
    public const string WITH_RESOURCES = 'gingerminds_cms_search_resources';

    /**
     * @param array<string, mixed>|null $resource
     */
    public function __construct(
        #[ApiProperty(identifier: true)]
        #[Groups([self::GROUP_LIST])]
        public readonly int $id,
        #[Groups([self::GROUP_LIST])]
        public readonly string $type,
        #[Groups([self::GROUP_LIST])]
        public readonly string $title,
        #[Groups([self::GROUP_LIST])]
        public readonly string $url,
        #[Groups([self::GROUP_LIST])]
        public readonly ?string $excerpt = null,
        #[Groups([self::GROUP_LIST])]
        #[SerializedName('published_at')]
        public readonly ?\DateTimeImmutable $publishedAt = null,
        #[Groups([self::GROUP_RESULT])]
        public ?array $resource = null,
    ) {
    }
}
