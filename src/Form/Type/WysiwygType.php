<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Type;

use Gingerminds\CmsBundle\Wysiwyg\WysiwygSanitizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Rich text (TipTap, Stimulus `gm-cms-wysiwyg`): the toolbar of a `gingerminds_cms.wysiwyg.presets`
 * preset (`preset`, default `default`), a minimum height of `rows` × 24px. The submitted HTML is
 * sanitized (WysiwygSanitizer), an empty editor gives null.
 *
 *     $builder->add('description', WysiwygType::class, ['preset' => 'minimal']);
 *
 * @extends AbstractType<string>
 */
class WysiwygType extends AbstractType
{
    /**
     * @param array<string, array{extensions: list<string>}> $presets
     */
    public function __construct(
        private readonly array $presets,
        private readonly WysiwygSanitizer $sanitizer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            $event->setData(\is_string($data) ? $this->sanitizer->sanitize($data) : null);
        });
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['extensions'] = $this->presets[$options['preset']]['extensions'];
        $view->vars['rows'] = $options['rows'];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'preset' => 'default',
                'rows' => 6,
            ])
            ->setAllowedValues('preset', array_keys($this->presets))
            ->setAllowedTypes('rows', 'int');
    }

    public function getParent(): string
    {
        return TextareaType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gm_cms_wysiwyg';
    }
}
