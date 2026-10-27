<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\CmsBundle\Form\Type\PublicationStatusType;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Model\TreeNodes;
use Gingerminds\CmsBundle\Page\PreviewUrls;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Gingerminds\MultisiteBundle\Form\Type\TranslationsType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The status: PublicationStatusType (the publication workflow).
 *
 * @extends AbstractType<PageInterface>
 */
class PageType extends AbstractType
{
    public function __construct(
        protected readonly ResourceRegistry $resources,
        protected readonly PageCategoryRepository $categories,
        protected readonly PreviewUrls $previewUrls,
        protected readonly bool $slugOverwriteOnCreate,
        protected readonly bool $slugOverwriteOnEdit,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $page = $builder->getData();
        $page = $page instanceof PageInterface ? $page : null;
        [$categories, $depths] = TreeNodes::parentChoices($this->categories->findTree(), null);

        $builder
            ->add('code', TextType::class, [
                'label' => 'page.field.code',
                'size' => 'lg',
            ])
            ->add('category', EntityType::class, [
                'label' => 'page.field.category',
                'class' => $this->resources->getEntityClass('page_category'),
                'choices' => $categories,
                'choice_label' => static fn (PageCategoryInterface $choice): string => str_repeat('— ', $depths[(int) $choice->getId()] ?? 0) . $choice,
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'page.placeholder.category',
                'size' => 'xl',
            ])
            ->add('mainVisual', FilePickerType::class, [
                'label' => 'page.field.main_visual',
                'accept' => ['image/*'],
                'required' => false,
                'size' => 'xl',
            ])
            ->add('thumbnail', FilePickerType::class, [
                'label' => 'page.field.thumbnail',
                'accept' => ['image/*'],
                'required' => false,
                'size' => 'xl',
            ])
            ->add('hiddenFromSearch', CheckboxType::class, [
                'label' => 'page.field.is_hidden_from_search',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch'],
                'size' => 'xl',
            ])
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'entry_type' => PageTranslationType::class,
                'entry_options' => ['slug_overwrite' => null === $page?->getId() ? $this->slugOverwriteOnCreate : $this->slugOverwriteOnEdit],
            ]);

        if ($page instanceof PageInterface) {
            $builder->add('status', PublicationStatusType::class, [
                'label' => 'page.field.status',
                'subject' => $page,
                'size' => 'xl',
            ]);
        }
    }

    /**
     * In each language: the slug input starts with the category path, the front preview URLs.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $page = $form->getData();

        if (!$page instanceof PageInterface) {
            return;
        }

        $previewUrls = $this->previewUrls->forPage($page);

        foreach ($view['translations'] as $languageId => $translation) {
            $path = $page->getCategory()?->getFullPath((int) $languageId) ?? '';
            $translation['slug']->vars['input_prefix'] = '/' . ('' === $path ? '' : $path . '/');
            $translation->vars['preview_urls'] = $previewUrls[(int) $languageId] ?? [];
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->resources->getEntityClass('page'),
            'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
        ]);
    }
}
