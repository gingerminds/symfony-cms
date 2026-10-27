<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Site;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Gingerminds\CmsBundle\Site\Entity\Site as CmsSite;
use Gingerminds\CmsBundle\Tests\Application\Kernel;
use Gingerminds\MultisiteBundle\Entity\Site\Site;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Form\Site\SiteType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Form\FormFactoryInterface;

/**
 * Without the CMS site in the multisite configuration: no main menu, the CMS site is not mapped.
 */
final class BundleSiteTest extends TestCase
{
    private static Kernel $kernel;

    public static function setUpBeforeClass(): void
    {
        self::$kernel = new Kernel('bundle_site', true);
        new Filesystem()->remove(self::$kernel->getCacheDir());
        self::$kernel->boot();
    }

    public static function tearDownAfterClass(): void
    {
        self::$kernel->shutdown();
    }

    public function testTheCmsSiteIsNotMapped(): void
    {
        $entityManager = $this->service(EntityManagerInterface::class);
        $entities = array_map(
            static fn (ClassMetadata $metadata): string => $metadata->getName(),
            array_filter($entityManager->getMetadataFactory()->getAllMetadata(), static fn (ClassMetadata $metadata): bool => !$metadata->isMappedSuperclass),
        );

        self::assertContains(Site::class, $entities);
        self::assertNotContains(CmsSite::class, $entities);
        self::assertSame(Site::class, $entityManager->getClassMetadata(SiteInterface::class)->getName());
    }

    public function testTheSiteFormHasNoMainMenu(): void
    {
        $form = $this->service(FormFactoryInterface::class)->create(SiteType::class, new Site());

        self::assertTrue($form->has('code'));
        self::assertFalse($form->has('main_menu'));
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $id
     *
     * @return T
     */
    private function service(string $id): object
    {
        /** @var T */
        return self::$kernel->getContainer()->get('test.service_container')->get($id);
    }
}
