<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a PageCategoryTranslation: its prefix is unique per language among the sibling categories
 * (same site and parent). Blank prefixes are ignored (Laravel PageCategoryRequest::uniquePrefixRule()).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueSiblingPrefix extends Constraint
{
    public string $message = 'gingerminds_cms.page_category.prefix_not_unique';

    public function __construct(?string $message = null, ?array $groups = null, mixed $payload = null)
    {
        parent::__construct(null, $groups, $payload);
        $this->message = $message ?? $this->message;
    }

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
