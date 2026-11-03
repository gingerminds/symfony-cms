<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslationInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniqueSlugInCategoryValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniqueSlugInCategory) {
            throw new UnexpectedTypeException($constraint, UniqueSlugInCategory::class);
        }

        if (!$value instanceof PageTranslationInterface) {
            throw new UnexpectedValueException($value, PageTranslationInterface::class);
        }

        $page = $value->getTranslatable();
        $language = $value->getLanguage();

        if (!$page instanceof PageInterface || !$language instanceof LanguageInterface || !$page->getSite() instanceof SiteInterface) {
            return;
        }

        if ($this->isTaken($value::class, $page, $value->getSlug(), (int) $language->getId())) {
            $this->context->buildViolation($constraint->message)->atPath('slug')->addViolation();
        }
    }

    /**
     * @param class-string<PageTranslationInterface> $class the translation entity
     */
    private function isTaken(string $class, PageInterface $page, ?string $slug, int $languageId): bool
    {
        $manager = $this->doctrine->getManagerForClass($class);

        if (!$manager instanceof EntityManagerInterface) {
            return false;
        }

        $qb = $manager->createQueryBuilder()
            ->select('COUNT(t.id)')
            ->from($class, 't')
            ->join('t.translatable', 'p')
            ->andWhere('t.language = :language')
            ->andWhere('p.site = :site')
            ->setParameter('language', $languageId)
            ->setParameter('site', $page->getSite()?->getId());

        if (null === $slug) {
            $qb->andWhere('t.slug IS NULL');
        } else {
            $qb->andWhere('t.slug = :slug')->setParameter('slug', $slug);
        }

        $category = $page->getCategory();

        if ($category instanceof PageCategoryInterface) {
            $qb->andWhere('p.category = :category')->setParameter('category', $category->getId());
        } else {
            $qb->andWhere('p.category IS NULL');
        }

        if (null !== $page->getId()) {
            $qb->andWhere('p.id <> :page')->setParameter('page', $page->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
