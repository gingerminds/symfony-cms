<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * A URL segment (slug, prefix): the submitted value is slugified by the server (`Mon Tracteur`
 * gives `mon-tracteur`), blank gives null. With `source` (a sibling field), the Stimulus
 * `gm-cms-slug-sync` fills it from that field while typing; `overwrite`: a filled value too.
 *
 *     $builder->add('slug', SlugType::class, ['source' => 'title']);
 *
 * @extends AbstractType<string>
 */
class SlugType extends AbstractType
{
    public function __construct(
        private readonly SluggerInterface $slugger,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            static fn (?string $slug): ?string => $slug,
            fn (?string $value): ?string => '' === trim((string) $value) ? null : $this->slugger->slug((string) $value)->lower()->toString(),
        ));
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        if (null === $options['source'] || !$view->parent instanceof FormView) {
            return;
        }

        $view->vars['attr'] = [
            ...$view->vars['attr'],
            'data-controller' => 'gm-cms-slug-sync',
            'data-gm-cms-slug-sync-source-value' => '#' . $view->parent->vars['id'] . '_' . $options['source'],
            'data-gm-cms-slug-sync-overwrite-value' => $options['overwrite'] ? 'true' : 'false',
        ];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'source' => null,
                'overwrite' => true,
                'required' => false,
            ])
            ->setAllowedTypes('source', ['null', 'string'])
            ->setAllowedTypes('overwrite', 'bool');
    }

    public function getParent(): string
    {
        return TextType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gm_cms_slug';
    }
}
