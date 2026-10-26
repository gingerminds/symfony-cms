<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Symfony\Component\DependencyInjection\Exception\LogicException;

/**
 * Two unrelated blocks declare the same key (a block extending the other one replaces it instead).
 */
final class DuplicateBlockKeyException extends LogicException
{
    public static function forKey(string $key, string $class, string $otherClass): self
    {
        return new self(\sprintf(
            'The blocks "%s" and "%s" both declare the key "%s": block keys must be unique (to replace a block, extend its class).',
            $class,
            $otherClass,
            $key,
        ));
    }
}
