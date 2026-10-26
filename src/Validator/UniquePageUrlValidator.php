<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Doctrine\Persistence\ManagerRegistry;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class UniquePageUrlValidator extends ConstraintValidator
{
    public function __construct(
        private readonly ManagerRegistry $doctrine,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof UniquePageUrl) {
            throw new UnexpectedTypeException($constraint, UniquePageUrl::class);
        }

        if (!$value instanceof PageTranslation) {
            throw new UnexpectedValueException($value, PageTranslation::class);
        }

        $page = $value->getTranslatable();
        $language = $value->getLanguage();

        // Only the published pages have a URL; a blank title is not translated.
        if (!$page instanceof PageInterface || !$page->isPublished() || '' === (string) $value->getTitle()) {
            return;
        }

        if (!$language instanceof LanguageInterface || !$page->getSite() instanceof SiteInterface) {
            return;
        }

        $path = $page->getFullPath($language);
        $url = $this->doctrine->getRepository(PageUrl::class)->findOneBy([
            'site' => $page->getSite()->getId(),
            'language' => $language->getId(),
            'path' => $path,
        ]);

        if ($url instanceof PageUrl && $url->getPage()->getId() !== $page->getId()) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ path }}', '/' . $path)
                ->setParameter('{{ page }}', (string) $url->getPage()->getCode())
                ->atPath('slug')
                ->addViolation();
        }
    }
}
