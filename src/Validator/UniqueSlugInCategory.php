<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a PageTranslation: its slug is unique per language among the pages of the same category
 * (the root without category included) and site. A blank slug counts: one category home page.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniqueSlugInCategory extends Constraint
{
    public string $message = 'gingerminds_cms.page.slug_not_unique';

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
