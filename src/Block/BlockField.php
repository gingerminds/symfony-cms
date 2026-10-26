<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Component\Validator\Constraint;

/**
 * One field of a block (BlockInterface::getFields()), immutable: every setter returns a copy.
 * The admin form (BlockDataType), the validation (BlockConstraints) and the stored data
 * (BlockContentNormalizer) are built from it.
 *
 *     BlockField::text('title')->required()->size('xl'),
 *     BlockField::wysiwyg('text')->preset('extended'),
 *     BlockField::repeater('links', [BlockField::text('label'), BlockField::text('url')])->itemLabel('...'),
 *
 * The label defaults to the `block.<block key>.field.<name>` translation key (`<repeater>_<name>`
 * for a repeater sub-field) of the block translation domain. `size` is a core form size
 * (tiny, sm, md, lg, xl).
 */
final class BlockField
{
    public const string TEXT = 'text';
    public const string TEXTAREA = 'textarea';
    public const string WYSIWYG = 'wysiwyg';
    public const string SELECT = 'select';
    public const string TOGGLE = 'toggle';
    public const string REPEATER = 'repeater';

    public private(set) bool $required = false;
    public private(set) ?string $label = null;
    public private(set) ?string $help = null;
    public private(set) ?string $size = null;
    public private(set) mixed $default = null;

    /** @var list<Constraint> extra constraints, after the ones of the type */
    public private(set) array $constraints = [];

    /** WYSIWYG: a `gingerminds_cms.wysiwyg.presets` preset. */
    public private(set) string $preset = 'default';

    /** WYSIWYG: minimum height, in lines. */
    public private(set) int $rows = 8;

    /** @var array<string, string> select: value => label (translation key of the block domain) */
    public private(set) array $choices = [];

    /** @var list<BlockField> repeater: the fields of a row (one level: no repeater) */
    public private(set) array $fields = [];

    /** Repeater: label of the "add" button, of a row ("<label> <number>"). */
    public private(set) ?string $addLabel = null;
    public private(set) ?string $itemLabel = null;

    private function __construct(
        public readonly string $name,
        public readonly string $type,
    ) {
    }

    public static function text(string $name): self
    {
        return new self($name, self::TEXT);
    }

    public static function textarea(string $name): self
    {
        return new self($name, self::TEXTAREA)->size('xl');
    }

    public static function wysiwyg(string $name): self
    {
        return new self($name, self::WYSIWYG)->size('xl');
    }

    /**
     * @param array<string, string> $choices value => label
     */
    public static function select(string $name, array $choices): self
    {
        return new self($name, self::SELECT)->with('choices', $choices);
    }

    public static function toggle(string $name): self
    {
        return new self($name, self::TOGGLE)->with('default', false);
    }

    /**
     * @param list<BlockField> $fields
     */
    public static function repeater(string $name, array $fields): self
    {
        foreach ($fields as $field) {
            if (self::REPEATER === $field->type) {
                throw new \InvalidArgumentException(\sprintf('The repeater "%s" cannot hold the repeater "%s".', $name, $field->name));
            }
        }

        return new self($name, self::REPEATER)->size('xl')->with('fields', $fields)->with('default', []);
    }

    public function required(bool $required = true): self
    {
        return $this->with('required', $required);
    }

    public function label(?string $label): self
    {
        return $this->with('label', $label);
    }

    public function help(?string $help): self
    {
        return $this->with('help', $help);
    }

    public function size(?string $size): self
    {
        return $this->with('size', $size);
    }

    public function default(mixed $default): self
    {
        return $this->with('default', $default);
    }

    public function constraints(Constraint ...$constraints): self
    {
        return $this->with('constraints', array_values($constraints));
    }

    public function preset(string $preset): self
    {
        return $this->with('preset', $preset);
    }

    public function rows(int $rows): self
    {
        return $this->with('rows', $rows);
    }

    public function addLabel(?string $addLabel): self
    {
        return $this->with('addLabel', $addLabel);
    }

    public function itemLabel(?string $itemLabel): self
    {
        return $this->with('itemLabel', $itemLabel);
    }

    private function with(string $property, mixed $value): self
    {
        $field = clone $this;
        $field->{$property} = $value;

        return $field;
    }
}
