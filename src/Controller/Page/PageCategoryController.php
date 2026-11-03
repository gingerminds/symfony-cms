<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Controller\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Page\PageUrlSyncer;
use Gingerminds\CoreBundle\Controller\AbstractTreeCrudController;
use Gingerminds\CoreBundle\Controller\ValueResolver\ResourceEntityValueResolver;
use Gingerminds\CoreBundle\Security\Voter\ResourceVoter;
use Gingerminds\MultisiteBundle\Context\SiteContext;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Page categories of the current site: a drag & drop tree.
 */
class PageCategoryController extends AbstractTreeCrudController
{
    public function __construct(
        protected readonly SiteContext $siteContext,
        protected readonly PageUrlSyncer $urlSyncer,
    ) {
    }

    #[Route('/{id}/delete', name: 'delete', requirements: ['id' => self::ID_REQUIREMENT], methods: ['POST'])]
    #[IsGranted(ResourceVoter::DELETE, subject: 'entity')]
    #[IsCsrfTokenValid(new Expression('"delete-" ~ request.attributes.get("id")'))]
    public function delete(#[ValueResolver(ResourceEntityValueResolver::class)] object $entity): Response
    {
        $response = parent::delete($entity);
        $this->flashUrlConflicts();

        return $response;
    }

    // The site is set before the validation: a category cannot be created without current site.
    protected function createEntity(): object
    {
        $category = parent::createEntity();

        if ($category instanceof PageCategoryInterface && $this->siteContext->has()) {
            $category->setSite($this->siteContext->site());
        }

        return $category;
    }

    protected function redirectAfterSave(object $entity, bool $isNew): Response
    {
        $this->flashUrlConflicts();

        return parent::redirectAfterSave($entity, $isNew);
    }

    /**
     * A page whose new URL path is taken by another page keeps its former one (PageUrlSyncer).
     */
    protected function flashUrlConflicts(): void
    {
        foreach ($this->urlSyncer->getConflicts() as $conflict) {
            $this->addFlash('warning', $this->trans('page_category.flash.url_conflict', [
                '%page%' => $conflict->page->getCode(),
                '%language%' => $conflict->language->getIso(),
                '%path%' => '/' . $conflict->path,
                '%owner%' => $conflict->owner->getCode(),
            ], GingermindsCmsBundle::TRANSLATION_DOMAIN));
        }
    }
}
