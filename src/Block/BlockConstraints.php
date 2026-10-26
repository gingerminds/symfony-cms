<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * The validation of the block fields, shared by the block form (one block) and ValidBlocks (the
 * whole content saved with the page): required, type of the value, select choices, the field
 * extra constraints.
 */
final class BlockConstraints
{
    /**
     * The constraints of a field value (a repeater: the list, its rows validated by forData()).
     *
     * @return list<Constraint>
     */
    public static function forField(BlockField $field): array
    {
        $constraints = match ($field->type) {
            BlockField::TOGGLE => [new Assert\Type('bool')],
            BlockField::SELECT => [new Assert\Type('string'), new Assert\Choice(choices: array_map(strval(...), array_keys($field->choices)))],
            BlockField::REPEATER => [new Assert\Type('array'), ...($field->required ? [new Assert\Count(min: 1)] : [])],
            default => [new Assert\Type('string')],
        };

        // A toggle always has a value; a repeater requires one row (Count).
        if ($field->required && !\in_array($field->type, [BlockField::TOGGLE, BlockField::REPEATER], true)) {
            array_unshift($constraints, new Assert\NotBlank());
        }

        return [...$constraints, ...$field->constraints];
    }

    /**
     * The data of a block: each field, the rows of the repeaters included. Unknown keys are allowed
     * (BlockContentNormalizer removes them), missing ones are not validated.
     *
     * @param list<BlockField> $fields
     */
    public static function forData(array $fields): Assert\Collection
    {
        $constraints = [];

        foreach ($fields as $field) {
            $constraints[$field->name] = BlockField::REPEATER === $field->type
                ? [new Assert\Sequentially([...self::forField($field), new Assert\All([new Assert\Type('array'), self::forData($field->fields)])])]
                : self::forField($field);
        }

        return new Assert\Collection(fields: $constraints, allowExtraFields: true, allowMissingFields: true);
    }
}
