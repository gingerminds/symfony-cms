<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * The translated fields of a page category and its full path.
 */
trait PageCategoryTranslatedFieldsTrait
{
    public function getName(): ?string
    {
        $translation = $this->getCurrentTranslation();

        return $translation instanceof PageCategoryTranslation ? $translation->getName() : null;
    }

    public function getPrefix(LanguageInterface|int|null $language = null): ?string
    {
        $translation = null === $language ? $this->getCurrentTranslation() : $this->getTranslation($language, false);

        return $translation instanceof PageCategoryTranslation ? $translation->getPrefix() : null;
    }

    public function getFullPath(LanguageInterface|int|null $language = null): string
    {
        $segments = [];

        for ($category = $this; $category instanceof PageCategoryInterface; $category = $category->getParent()) {
            $prefix = $category->getPrefix($language);

            if (null !== $prefix && '' !== $prefix) {
                array_unshift($segments, $prefix);
            }
        }

        return implode('/', $segments);
    }
}
