<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

/**
 * The entities referenced by the fields of a type (`file`, `media`, or a project type), resolved in
 * the content served by the API and given to the previews (ContentReferenceResolver). Tagged
 * `gingerminds_cms.block_reference_resolver` (autoconfigured), indexed by getFieldType().
 */
interface BlockReferenceResolverInterface
{
    public const string TAG = 'gingerminds_cms.block_reference_resolver';

    /**
     * The BlockField type whose values are ids of this resolver.
     */
    public static function getFieldType(): string;

    /**
     * Every id of the type in a content, in one query.
     *
     * @param list<int|string> $ids
     *
     * @return array<int|string, object> the found entities, by id
     */
    public function load(array $ids): array;

    /**
     * An entity as served by the API.
     *
     * @return array<string, mixed>
     */
    public function normalize(object $entity): array;
}
