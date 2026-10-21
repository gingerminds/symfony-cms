<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Page;

use Gingerminds\CmsBundle\Entity\Page\PageCategoryInterface;
use Gingerminds\CmsBundle\GingermindsCmsBundle;
use Gingerminds\CmsBundle\Repository\Page\PageCategoryRepository;
use Gingerminds\CoreBundle\Resource\ResourceRegistry;
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
 * The parent is chosen in the tree of the site, the category and its descendants excluded.
 *
 * @extends AbstractType<PageCategoryInterface>
 */
class PageCategoryType extends AbstractType
{
    public function __construct(
        protected readonly ResourceRegistry $resources,
        protected readonly PageCategoryRepository $categories,
        protected readonly bool $slugOverwriteOnCreate,
        protected readonly bool $slugOverwriteOnEdit,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $category = $builder->getData();
        $category = $category instanceof PageCategoryInterface ? $category : null;
        [$choices, $depths] = $this->parentChoices($category);
        $isNew = null === $category?->getId();

        $builder
            ->add('code', TextType::class, [
                'label' => 'page_category.field.code',
                'size' => 'lg',
            ])
            ->add('parent', EntityType::class, [
                'label' => 'page_category.field.parent',
                'class' => $this->resources->getEntityClass('page_category'),
                'choices' => $choices,
                'choice_label' => static fn (PageCategoryInterface $choice): string => str_repeat('— ', $depths[(int) $choice->getId()] ?? 0) . $choice,
                'choice_translation_domain' => false,
                'required' => false,
                'placeholder' => 'page_category.placeholder.parent',
                'size' => 'xl',
            ])
            ->add('unique', CheckboxType::class, [
                'label' => 'page_category.field.is_unique',
                'help' => 'page_category.help.is_unique',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch'],
                'size' => 'xl',
            ])
            ->add('hiddenFromSearch', CheckboxType::class, [
                'label' => 'page_category.field.is_hidden_from_search',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch'],
                'size' => 'xl',
            ])
            ->add('translations', TranslationsType::class, [
                'label' => false,
                'entry_type' => PageCategoryTranslationType::class,
                'entry_options' => ['slug_overwrite' => $isNew ? $this->slugOverwriteOnCreate : $this->slugOverwriteOnEdit],
            ]);
    }

    /**
     * The prefix input starts with the full path of the parent in each language.
     */
    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $parent = $form->getData() instanceof PageCategoryInterface ? $form->getData()->getParent() : null;

        foreach ($view['translations'] as $languageId => $translation) {
            $path = $parent?->getFullPath((int) $languageId) ?? '';
            $translation['prefix']->vars['input_prefix'] = '/' . ('' === $path ? '' : $path . '/');
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->resources->getEntityClass('page_category'),
            'translation_domain' => GingermindsCmsBundle::TRANSLATION_DOMAIN,
        ]);
    }

    /**
     * @return array{list<PageCategoryInterface>, array<int, int>} the choices and their depth by id
     */
    private function parentChoices(?PageCategoryInterface $category): array
    {
        $choices = [];
        $depths = [];
        $excludedDepth = null;

        foreach ($this->categories->findFlatTree() as [$choice, $depth]) {
            // The category and its descendants (the following deeper categories) cannot be its parent.
            if (null !== $excludedDepth && $depth > $excludedDepth) {
                continue;
            }

            $excludedDepth = null;

            if (null !== $category?->getId() && $choice->getId() === $category->getId()) {
                $excludedDepth = $depth;

                continue;
            }

            $choices[] = $choice;
            $depths[(int) $choice->getId()] = $depth;
        }

        return [$choices, $depths];
    }
}
