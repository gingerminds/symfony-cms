<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a PageInterface: a category flagged `is_unique` holds one page only
 * (Laravel PageCategoryUniquenessValidator).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class SinglePageCategory extends Constraint
{
    public string $message = 'gingerminds_cms.page.category_taken';

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
