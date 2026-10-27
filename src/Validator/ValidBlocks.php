<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a content (`[{uid, type, data}]`, BlockCanvasType): each block has a uid and a known type,
 * its data is valid for its fields (BlockConstraints). The violations are at `[<index>]`, their
 * message names the field.
 */
#[\Attribute(\Attribute::TARGET_PROPERTY | \Attribute::TARGET_METHOD)]
final class ValidBlocks extends Constraint
{
    public string $invalidMessage = 'gingerminds_cms.block.invalid';
    public string $unknownTypeMessage = 'gingerminds_cms.block.unknown_type';
    public string $fieldMessage = 'gingerminds_cms.block.field_error';

    public function __construct(?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
    }
}
