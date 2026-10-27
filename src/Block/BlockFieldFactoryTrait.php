<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

/**
 * The constructors of BlockField, one per field type.
 */
trait BlockFieldFactoryTrait
{
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

    /**
     * A file picked in the media library (FilePickerType): its id. Images by default, see accept().
     */
    public static function file(string $name): self
    {
        return new self($name, self::FILE);
    }

    /**
     * A media picked in the media picker (MediaSelectType): its id, a list of ids when multiple().
     */
    public static function media(string $name): self
    {
        return new self($name, self::MEDIA);
    }
}
