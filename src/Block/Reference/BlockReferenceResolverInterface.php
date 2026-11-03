<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

use Symfony\Component\Form\FormTypeInterface;

/**
 * The entities referenced by the fields of a form type (FilePickerType, MediaSelectType, or a project
 * type), resolved in the content served by the API and given to the previews
 * (ContentReferenceResolver). Tagged `gingerminds_cms.block_reference_resolver` (autoconfigured),
 * indexed by getFormType().
 */
interface BlockReferenceResolverInterface
{
    public const string TAG = 'gingerminds_cms.block_reference_resolver';

    /**
     * The form type whose values (an id, a list with `multiple`) are ids of this resolver; the
     * fields of a type extending it too.
     *
     * @return class-string<FormTypeInterface<mixed>>
     */
    public static function getFormType(): string;

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
