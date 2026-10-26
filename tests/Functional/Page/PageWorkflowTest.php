<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Functional\Page;

use Gingerminds\CmsBundle\Entity\Page\Page;
use Gingerminds\CmsBundle\Entity\Page\PageTranslation;
use Gingerminds\CmsBundle\Form\Type\PublicationStatusType;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\PublishableInterface;
use Gingerminds\CmsBundle\Model\PublishableTrait;
use Gingerminds\CmsBundle\Tests\Functional\ApiTestCase;
use Gingerminds\MediaManagerBundle\File\Reference\FileReferenceRegistry;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Workflow\Transition;
use Symfony\Component\Workflow\WorkflowInterface;
use Twig\Environment;

final class PageWorkflowTest extends ApiTestCase
{
    public function testTheBundleTransitionsSetThePublicationDates(): void
    {
        $workflow = $this->workflow();
        $page = new Page();

        self::assertSame(['publish', 'submit'], array_map(static fn (Transition $transition): string => $transition->getName(), $workflow->getEnabledTransitions($page)));

        $workflow->apply($page, 'publish');
        self::assertTrue($page->isPublished());
        self::assertNotNull($page->getPublishedAt());

        $workflow->apply($page, 'archive');
        self::assertSame(PublicationStatus::ARCHIVED, $page->getStatus());
        self::assertNotNull($page->getArchivedAt());
        self::assertNull($page->getPublishedAt());

        $workflow->apply($page, 'restore');
        self::assertSame(PublicationStatus::DRAFT, $page->getStatus());
        self::assertNull($page->getArchivedAt());
        self::assertFalse($workflow->can($page, 'archive'));
    }

    public function testAProjectStatusIsAddedToTheBundleOnes(): void
    {
        $workflow = $this->workflow();
        $page = new Page();

        self::assertSame(['draft', 'published', 'archived', 'review'], array_values($workflow->getDefinition()->getPlaces()));

        $workflow->apply($page, 'submit');
        self::assertSame('review', $page->getStatus());
        self::assertNull($page->getPublishedAt(), 'A project place leaves the dates as they are.');

        $workflow->apply($page, 'approve');
        self::assertTrue($page->isPublished());
        self::assertNotNull($page->getPublishedAt());
    }

    public function testThePublicationStatusIsReusableOnAnotherResource(): void
    {
        $event = new class implements PublishableInterface {
            use PublishableTrait;
        };
        $event->setStatus(PublicationStatus::PUBLISHED);

        $form = self::getContainer()->get(FormFactoryInterface::class)->createBuilder(FormType::class, null, ['csrf_protection' => false])
            ->add('status', PublicationStatusType::class, ['subject' => $event])
            ->getForm();

        self::assertSame(['published', 'draft', 'archived'], array_values($form->get('status')->getConfig()->getOption('choices')));

        $form->submit(['status' => 'archived']);
        self::assertTrue($form->isValid());
        self::assertSame(PublicationStatus::ARCHIVED, $event->getStatus());
        self::assertNotNull($event->getArchivedAt());

        $badge = self::getContainer()->get(Environment::class)->render('@GingermindsCms/publication/_status.html.twig', ['status' => 'review']);
        self::assertStringContainsString('bg-info', $badge, 'The project color of a project status.');
    }

    public function testTheVisualsOfATranslationAreUsagesOfThePage(): void
    {
        $site = $this->site('main');
        $file = $this->fixtures->file('cover.png');
        $page = $this->fixtures->page($site, 'tractor', ['fr' => ['Tracteur X', 'tracteur-x']]);
        $translation = $page->getTranslations()->first();
        self::assertInstanceOf(PageTranslation::class, $translation);
        $translation->setMainVisual($file);
        $this->entityManager()->flush();

        /** @var FileReferenceRegistry $registry */
        $registry = self::getContainer()->get('test.file_reference_registry');
        $usages = $registry->usages([$file])[$file->getId()];

        self::assertCount(1, $usages);
        self::assertSame('Tracteur X (fr)', $usages[0]->title);
        self::assertSame('/admin/pages/' . $page->getId() . '/edit', $usages[0]->editUrl);
    }

    private function workflow(): WorkflowInterface
    {
        /** @var WorkflowInterface */
        return self::getContainer()->get('test.page_workflow');
    }
}
