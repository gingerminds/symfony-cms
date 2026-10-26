<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

/**
 * The publication workflow (`gingerminds_cms_publication`) of the PublishableInterface entities
 * (pages...): the statuses known by the bundle. A project adds its own places and transitions in
 * its workflow configuration: the status is a string.
 */
final class PublicationStatus
{
    public const string WORKFLOW = 'gingerminds_cms_publication';

    public const string DRAFT = 'draft';
    public const string PUBLISHED = 'published';
    public const string ARCHIVED = 'archived';

    public const array PLACES = [self::DRAFT, self::PUBLISHED, self::ARCHIVED];

    /**
     * @var array<string, array{string, string}> transition name => [from, to]
     */
    public const array TRANSITIONS = [
        'publish' => [self::DRAFT, self::PUBLISHED],
        'unpublish' => [self::PUBLISHED, self::DRAFT],
        'archive' => [self::PUBLISHED, self::ARCHIVED],
        'restore' => [self::ARCHIVED, self::DRAFT],
    ];

    private function __construct()
    {
    }

    /**
     * The translation key of each status (GingermindsCms domain), e.g. for a `select` filter.
     *
     * @param iterable<string> $statuses
     *
     * @return array<string, string> status => `publication.status.<status>`
     */
    public static function labels(iterable $statuses = self::PLACES): array
    {
        $labels = [];

        foreach ($statuses as $status) {
            $labels[$status] = 'publication.status.' . $status;
        }

        return $labels;
    }
}
