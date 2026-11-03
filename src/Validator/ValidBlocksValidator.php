<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Gingerminds\CmsBundle\Block\BlockRegistry;
use Gingerminds\CmsBundle\Block\ContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Each block submitted to its form (BlockRegistry::submit()): an error of a field is a violation of
 * the block, its message naming the field (`Cards 2 › Image`).
 */
final class ValidBlocksValidator extends ConstraintValidator
{
    public function __construct(
        private readonly BlockRegistry $registry,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$constraint instanceof ValidBlocks) {
            throw new UnexpectedTypeException($constraint, ValidBlocks::class);
        }

        if (null === $value) {
            return;
        }

        if (!\is_array($value)) {
            throw new UnexpectedValueException($value, 'array');
        }

        foreach ($value as $index => $item) {
            $path = '[' . $index . ']';

            if (!\is_array($item) || !\is_string($item['uid'] ?? null) || '' === $item['uid'] || !\is_string($item['type'] ?? null) || !\is_array($item['data'] ?? null)) {
                $this->context->buildViolation($constraint->invalidMessage)->atPath($path)->addViolation();

                continue;
            }

            $block = $this->registry->find($item['type']);

            if (!$block instanceof ContentBlock) {
                $this->context->buildViolation($constraint->unknownTypeMessage)
                    ->setParameter('{{ type }}', $item['type'])
                    ->atPath($path)
                    ->addViolation();

                continue;
            }

            foreach ($this->registry->submit($block, $item['data'])->getErrors(true) as $error) {
                if (!$error instanceof FormError) {
                    continue;
                }

                $this->context->buildViolation($constraint->fieldMessage)
                    ->setParameter('{{ field }}', $this->fieldLabel($block, $error->getOrigin()))
                    ->setParameter('{{ message }}', $error->getMessage())
                    ->atPath($path)
                    ->addViolation();
            }
        }
    }

    /**
     * The label of a field, its rows and parent fields first: `Cards 2 › Image`.
     *
     * @param FormInterface<mixed>|null $form
     */
    private function fieldLabel(ContentBlock $block, ?FormInterface $form): string
    {
        $forms = [];

        for (; $form?->getParent() instanceof FormInterface; $form = $form->getParent()) {
            $forms[] = $form;
        }

        $labels = [];

        foreach (array_reverse($forms) as $field) {
            if ($field->getParent()?->getConfig()->getType()->getInnerType() instanceof BlockCollectionType) {
                $labels[\count($labels) - 1] .= ' ' . ((int) $field->getName() + 1);
            } else {
                $labels[] = $this->translator->trans($this->labelKey($field), [], $this->domain($field) ?? $block->translationDomain);
            }
        }

        return implode(' › ', $labels);
    }

    /**
     * Its `label`, else its `label_format` or the closest one of its parents.
     *
     * @param FormInterface<mixed> $form
     */
    private function labelKey(FormInterface $form): string
    {
        $label = $form->getConfig()->getOption('label');

        if (\is_string($label) && '' !== $label) {
            return $label;
        }

        for ($ancestor = $form; $ancestor instanceof FormInterface; $ancestor = $ancestor->getParent()) {
            $format = $ancestor->getConfig()->getOption('label_format');

            if (\is_string($format) && '' !== $format) {
                return str_replace('%name%', $form->getName(), $format);
            }
        }

        return $form->getName();
    }

    /**
     * @param FormInterface<mixed> $form
     */
    private function domain(FormInterface $form): ?string
    {
        for ($ancestor = $form; $ancestor instanceof FormInterface; $ancestor = $ancestor->getParent()) {
            $domain = $ancestor->getConfig()->getOption('translation_domain');

            if (\is_string($domain)) {
                return $domain;
            }
        }

        return null;
    }
}
