<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Unit\Entity;

use Gingerminds\CmsBundle\Entity\Page\PageCategory;
use Gingerminds\CmsBundle\Entity\Page\PageCategoryTranslation;
use Gingerminds\MultisiteBundle\Entity\Language\Language;
use PHPUnit\Framework\TestCase;

final class PageCategoryFullPathTest extends TestCase
{
    public function testTheFullPathJoinsThePrefixesOfALanguageAndSkipsTheBlankOnes(): void
    {
        $fr = $this->language(1, 'fr');
        $en = $this->language(2, 'en');
        $products = $this->category(null, [[$fr, 'produits'], [$en, 'products']]);
        $range = $this->category($products, [[$fr, null], [$en, 'range']]);
        $tractors = $this->category($range, [[$fr, 'tracteurs']]);

        self::assertSame('produits/tracteurs', $tractors->getFullPath($fr));
        // No fallback: a language without prefix adds no segment.
        self::assertSame('products/range', $tractors->getFullPath(2));
        self::assertSame('produits', $range->getFullPath($fr));
        self::assertNull($range->getPrefix($fr));
        self::assertSame('range', $range->getPrefix($en));
    }

    /**
     * @param list<array{Language, ?string}> $prefixes
     */
    private function category(?PageCategory $parent, array $prefixes): PageCategory
    {
        $category = new PageCategory();
        $category->setParent($parent);

        foreach ($prefixes as [$language, $prefix]) {
            $translation = new PageCategoryTranslation();
            $translation->setLanguage($language);
            $translation->setPrefix($prefix);
            $category->addTranslation($translation);
        }

        return $category;
    }

    private function language(int $id, string $iso): Language
    {
        $language = new Language();
        $language->setIso($iso);
        new \ReflectionProperty($language, 'id')->setValue($language, $id);

        return $language;
    }
}
