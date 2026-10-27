<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\Url;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

final class LinkUrlValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof LinkUrl) {
            throw new UnexpectedTypeException($constraint, LinkUrl::class);
        }

        if (null === $value || '' === $value) {
            return;
        }

        $value = \is_scalar($value) ? (string) $value : '';
        $isPath = 1 === preg_match('#^/(?!/)\S*$#', $value);

        if ($isPath || 0 === \count($this->context->getValidator()->validate($value, new Url(requireTld: true)))) {
            return;
        }

        $this->context->buildViolation($constraint->message)->addViolation();
    }
}
