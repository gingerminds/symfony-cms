<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Page;

use Gingerminds\CmsBundle\Entity\Page\Page as BundlePage;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation as BundlePageTranslation;
use Gingerminds\CmsBundle\Entity\Page\PageTranslationInterface;
use Gingerminds\CmsBundle\Entity\Page\PageUrl;
use Gingerminds\CmsBundle\Form\Page\PageTranslationType;
use Gingerminds\CmsBundle\Tests\Application\Override\Page;
use Gingerminds\CmsBundle\Tests\Application\Override\PageTranslation;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * `test_override` environment: the page and its translation replaced by project entities
 * (`resources.page.entity`, `resources.page_translation.entity`), a translated field added.
 */
final class OverriddenPageTest extends ApiTestCase
{
    public function testTheProjectEntitiesReplaceTheBundleOnes(): void
    {
        $classes = $this->entityManager()->getConfiguration()->getMetadataDriverImpl()?->getAllClassNames() ?? [];
        self::assertNotContains(BundlePage::class, $classes);
        self::assertNotContains(BundlePageTranslation::class, $classes);

        $metadata = $this->entityManager()->getClassMetadata(Page::class);
        self::assertSame(PageTranslation::class, $metadata->getAssociationTargetClass('translations'));
        self::assertSame(Page::class, $this->entityManager()->getClassMetadata(PageTranslation::class)->getAssociationTargetClass('translatable'));
        self::assertSame(Page::class, $this->entityManager()->getClassMetadata(PageInterface::class)->getName());
        self::assertSame(PageTranslation::class, $this->entityManager()->getClassMetadata(PageTranslationInterface::class)->getName());

        $form = self::getContainer()->get('form.factory')->create(PageTranslationType::class);
        self::assertSame(PageTranslation::class, $form->getConfig()->getDataClass());
    }

    public function testAPageWithItsOwnTranslatedFieldIsServed(): void
    {
        $site = $this->site('main');
        $page = new Page();
        $page->setSite($site);
        $page->setCode('about');
        $page->setStatus('published');
        $translation = new PageTranslation();
        $translation->setLanguage($site->getDefaultLanguage() ?? self::fail('No language.'));
        $translation->setTitle('À propos');
        $translation->setSlug('a-propos');
        $translation->setSubtitle('Qui sommes-nous');
        $page->addTranslation($translation);
        $this->entityManager()->persist($page);
        $this->entityManager()->flush();
        $this->entityManager()->clear();

        $stored = $this->entityManager()->find(Page::class, $page->getId());
        $storedTranslation = $stored?->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $storedTranslation);
        self::assertSame('Qui sommes-nous', $storedTranslation->getSubtitle());
        self::assertSame(['a-propos'], array_map(static fn (PageUrl $url): string => $url->getPath(), $stored->getUrls()->toArray()), 'URLs synced.');

        // The bundle #[ApiResource] applies to the project page.
        $data = $this->api('GET', '/api/pages/by-slug/a-propos', locale: 'fr');
        self::assertStatus(200);
        self::assertSame('À propos', $data['title']);
    }

    /**
     * @param array<string, mixed> $options
     */
    protected static function createKernel(array $options = []): KernelInterface
    {
        return parent::createKernel(['environment' => 'test_override', ...$options]);
    }
}
