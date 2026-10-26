<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Form\Block;

use Gingerminds\CmsBundle\Block\BlockContentNormalizer;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Validator\ValidBlocks;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Exception\TransformationFailedException;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;

/**
 * A content made of blocks (`[{uid, type, data}]`, e.g. `PageTranslation::content`), edited in a
 * canvas (Stimulus `gm-cms-blocks`): the blocks with their preview, the picker, the block form in a
 * modal (BlockController `form_route` / `validate_route`), drag & drop, the copy of the structure of
 * another language. The JSON of the hidden field is normalized (BlockContentNormalizer) and fully
 * revalidated (ValidBlocks) on save; an empty content gives null.
 *
 *     $builder->add('content', BlockCanvasType::class, ['label' => 'page.field.content']);
 *
 * @extends AbstractType<list<mixed>|null>
 */
class BlockCanvasType extends AbstractType
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly BlockContentNormalizer $normalizer,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer(new CallbackTransformer(
            // No block: an empty field, so an optional translation without content stays blank.
            static fn (mixed $content): string => \is_array($content) && [] !== $content ? json_encode(array_values($content), \JSON_THROW_ON_ERROR) : '',
            fn (mixed $json): ?array => $this->decode($json),
        ));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $content = $form->getData();

        if (!\is_array($content)) {
            $content = json_decode((string) $form->getViewData(), true);
        }

        [$errors, $globalErrors] = $this->splitErrors($form);
        $items = [];

        foreach (\is_array($content) ? array_values($content) : [] as $index => $item) {
            $item = \is_array($item) ? $item : [];
            $type = \is_string($item['type'] ?? null) ? $item['type'] : '';
            $block = $this->registry->find($type);
            $data = \is_array($item['data'] ?? null) ? $item['data'] : [];

            $items[] = [
                'uid' => \is_string($item['uid'] ?? null) && '' !== $item['uid'] ? $item['uid'] : bin2hex(random_bytes(16)),
                'type' => $type,
                'block' => $block,
                'data' => $block instanceof BlockInterface ? [...$this->registry->getDefaults($block), ...$data] : $data,
                'errors' => $errors[$index] ?? [],
            ];
        }

        $view->vars['items'] = $items;
        $view->vars['global_errors'] = $globalErrors;
        $view->vars['picker'] = $this->registry->active();
        $view->vars['form_url'] = $this->urlGenerator->generate($options['form_route'], ['key' => '__key__']);
        $view->vars['validate_url'] = $this->urlGenerator->generate($options['validate_route'], ['key' => '__key__']);
        $view->vars['language'] = $this->language($view, $form);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'required' => false,
                'error_bubbling' => false,
                'size' => 'xl',
                'constraints' => [new ValidBlocks()],
                'invalid_message' => 'gingerminds_cms.block.invalid_content',
                'form_route' => 'gingerminds_cms_page_block_form',
                'validate_route' => 'gingerminds_cms_page_block_validate',
            ])
            ->setAllowedTypes('form_route', 'string')
            ->setAllowedTypes('validate_route', 'string');
    }

    public function getParent(): string
    {
        return HiddenType::class;
    }

    public function getBlockPrefix(): string
    {
        return 'gm_cms_blocks';
    }

    /**
     * The language of the canvas (for the copy of the structure): its multisite translations pane,
     * else its translation.
     *
     * @param FormInterface<mixed> $form
     */
    private function language(FormView $view, FormInterface $form): ?string
    {
        $pane = $view->parent;
        $languages = $pane?->parent?->vars['translation_languages'] ?? [];
        $iso = \is_array($languages) && $pane instanceof FormView ? ($languages[$pane->vars['name']]['iso'] ?? null) : null;
        $translation = $form->getParent()?->getData();

        if (!\is_string($iso) && $translation instanceof TranslationInterface) {
            $iso = $translation->getLanguage()?->getIso();
        }

        return \is_string($iso) ? mb_strtoupper($iso) : null;
    }

    /**
     * @return list<mixed>|null
     */
    private function decode(mixed $json): ?array
    {
        if (null === $json || '' === $json) {
            return null;
        }

        try {
            $content = json_decode((string) $json, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new TransformationFailedException('The content is not valid JSON.', 0, $exception);
        }

        if (!\is_array($content) || !array_is_list($content)) {
            throw new TransformationFailedException('The content is not a list of blocks.');
        }

        return [] === $content ? null : $this->normalizer->normalize($content);
    }

    /**
     * The messages of each block (ValidBlocks violation at `[<index>]`), and the other ones.
     *
     * @param FormInterface<mixed> $form
     *
     * @return array{array<int, list<string>>, list<string>}
     */
    private function splitErrors(FormInterface $form): array
    {
        $errors = [];
        $globalErrors = [];

        foreach ($form->getErrors() as $error) {
            if (!$error instanceof FormError) {
                continue;
            }

            $cause = $error->getCause();
            $path = $cause instanceof ConstraintViolationInterface ? $cause->getPropertyPath() : '';

            if (1 === preg_match('/\[(\d+)\]$/', $path, $matches)) {
                $errors[(int) $matches[1]][] = $error->getMessage();
            } else {
                $globalErrors[] = $error->getMessage();
            }
        }

        return [$errors, $globalErrors];
    }
}
