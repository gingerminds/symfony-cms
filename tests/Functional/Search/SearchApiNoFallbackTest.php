<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Search;

use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * `gingerminds_multisite.translation_fallback: false`: the search runs in the asked language only.
 */
final class SearchApiNoFallbackTest extends ApiTestCase
{
    public function testOnlyTheAskedLanguageIsSearched(): void
    {
        $fr = $this->fixtures->language('fr');
        $site = $this->fixtures->site('main', [$fr, $this->fixtures->language('en')], $fr);
        $this->fixtures->page($site, 'tractor', ['fr' => ['Tracteur rouge', 'tracteur-rouge'], 'en' => ['Red tractor', 'red-tractor']], status: 'published');
        $this->fixtures->page($site, 'about', ['fr' => ['À propos', 'a-propos']], status: 'published');

        self::assertSame(['Red tractor'], array_column($this->api('GET', '/api/search-indices', locale: 'en')['member'], 'title'));
        self::assertEqualsCanonicalizing(['Tracteur rouge', 'À propos'], array_column($this->api('GET', '/api/search-indices', locale: 'fr')['member'], 'title'));
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_no_fallback', ...$options]);
    }
}
