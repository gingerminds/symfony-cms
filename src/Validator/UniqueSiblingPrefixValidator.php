<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslationInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueSiblingPrefixValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSiblingPrefix) {
            throw new UnexpectedTypeException($constraint, UniqueSiblingPrefix::class);
        }

        if (!$value instanceof PageCategoryTranslationInterface) {
            throw new UnexpectedValueException($value, PageCategoryTranslationInterface::class);
        }

        $category = $value->getTranslatable();
        $prefix = $value->getPrefix();
        $language = $value->getLanguage();

        if (null === $prefix || !$category instanceof PageCategoryInterface || !$language instanceof LanguageInterface || !$category->getSite() instanceof SiteInterface) {
            return;
        }

        if ($this->isTakenBySibling($value::class, $category, $prefix, (int) $language->getId())) {
            $this->context->buildViolation($constraint->message)->atPath('prefix')->addViolation();
        }
    }

    /**
     * @param class-string<PageCategoryTranslationInterface> $class the translation entity
     */
    private function isTakenBySibling(string $class, PageCategoryInterface $category, string $prefix, int $languageId): bool
    {
        $manager = $this->doctrine->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            return false;
        }

        $qb = $manager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from($class, 't')
            ->join('t.translatable', 'c')
            ->andWhere('t.language = :language')
            ->andWhere('t.prefix = :prefix')
            ->andWhere('c.site = :site')
            ->setParameter('language', $languageId)
            ->setParameter('prefix', $prefix)
            ->setParameter('site', $category->getSite()?->getId());

        $parent = $category->getParent();

        if ($parent instanceof PageCategoryInterface) {
            $qb->andWhere('c.parent = :parent')->setParameter('parent', $parent->getId());
        } else {
            $qb->andWhere('c.parent IS NULL');
        }

        if (null !== $category->getId()) {
            $qb->andWhere('c.id <> :category')->setParameter('category', $category->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
