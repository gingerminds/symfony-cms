<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Repository\Page\PageRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class SinglePageCategoryValidator extends ConstraintValidator
{
    public function __construct(
        private readonly PageRepository $pages,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof SinglePageCategory) {
            throw new UnexpectedTypeException($constraint, SinglePageCategory::class);
        }

        if (!$value instanceof PageInterface) {
            throw new UnexpectedValueException($value, PageInterface::class);
        }

        $category = $value->getCategory();

        if ($category instanceof PageCategoryInterface && $category->isUnique() && $this->pages->countInCategory($category, $value) > 0) {
            $this->context->buildViolation($constraint->message)->atPath('category')->addViolation();
        }
    }
}
