<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;

/**
 * On a PageTranslation of a page to publish: its URL path (category path and slug) is not the
 * one of another published page of the site in this language (Laravel PageUrlCollisionValidator).
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final class UniquePageUrl extends Constraint
{
    public string $message = 'gingerminds_cms.page.url_taken';

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
