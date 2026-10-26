<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Api;

use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * `gingerminds_multisite.translation_fallback: false`: a page path is found in the asked
 * language only, an untranslated page keeps its place with null translated fields.
 */
final class PageApiNoFallbackTest extends ApiTestCase
{
    public function testAPathIsFoundInTheAskedLanguageOnly(): void
    {
        $fr = $this->fixtures->language('fr');
        $site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
        $this->fixtures->page($site, 'tractor', ['fr' => ['Tracteur', 'tracteur'], 'en' => ['Tractor', 'tractor']], status: 'published');
        $this->fixtures->page($site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');

        self::assertSame('Tractor', $this->api('GET', '/api/pages/by-slug/tractor', locale: 'en')['title']);
        $this->api('GET', '/api/pages/by-slug/tracteur', locale: 'en');
        self::assertStatus(404);

        $pages = array_column($this->api('GET', '/api/pages', locale: 'en')['member'], null, 'code');
        self::assertEqualsCanonicalizing(['about', 'tractor'], array_keys($pages));
        self::assertNull($pages['about']['title']);
        self::assertSame(['fr' => 'a-propos'], $pages['about']['switch_lang']);
    }

    public function testTheContentOfAnotherLanguageIsNotServed(): void
    {
        $fr = $this->fixtures->language('fr');
        $site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
        $page = $this->fixtures->page($site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');
        $translation = $page->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $translation);
        $translation->setContent([['uid' => 'a', 'type' => 'title_text', 'data' => ['title' => 'Bonjour']]]);
        $this->entityManager()->flush();

        self::assertSame('Bonjour', $this->api('GET', '/api/pages/' . $page->getId(), locale: 'fr')['content'][0]['data']['title']);
        self::assertNull($this->api('GET', '/api/pages/' . $page->getId(), locale: 'en')['content']);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_no_fallback', ...$options]);
    }
}
