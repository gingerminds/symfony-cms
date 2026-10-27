<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Validator;

use Gingerminds\CmsBundle\Block\BlockConstraints;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\CmsBundle\Block\BlockInterface;
use Gingerminds\CmsBundle\Block\BlockRegistry;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Contracts\Translation\TranslatorInterface;

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

            if (!$block instanceof BlockInterface) {
                $this->context->buildViolation($constraint->unknownTypeMessage)
                    ->setParameter('{{ type }}', $item['type'])
                    ->atPath($path)
                    ->addViolation();

                continue;
            }

            $this->validateData($block, $item['data'], $path, $constraint);
        }
    }

    /**
     * @param array<mixed> $data
     */
    private function validateData(BlockInterface $block, array $data, string $path, ValidBlocks $constraint): void
    {
        $fields = $this->registry->getFields($block);

        foreach ($this->context->getValidator()->validate($data, BlockConstraints::forData($fields)) as $violation) {
            $this->context->buildViolation($constraint->fieldMessage)
                ->setParameter('{{ field }}', $this->fieldLabel($block, $fields, $violation->getPropertyPath()))
                ->setParameter('{{ message }}', (string) $violation->getMessage())
                ->setInvalidValue($violation->getInvalidValue())
                ->atPath($path)
                ->addViolation();
        }
    }

    /**
     * The label of the field at a violation path: `[title]`, or `[links][0][url]` (a repeater row).
     *
     * @param list<BlockField> $fields
     */
    private function fieldLabel(BlockInterface $block, array $fields, string $propertyPath): string
    {
        preg_match_all('/\[([^\]]*)\]/', $propertyPath, $matches);
        [$name, $row, $subName] = [...$matches[1], null, null, null];
        $domain = $block->getTranslationDomain();

        foreach ($fields as $field) {
            if ($field->name !== $name) {
                continue;
            }

            $label = $this->translator->trans($this->registry->getFieldLabel($block, $field), [], $domain);

            foreach ($field->fields as $subField) {
                if ($subField->name === $subName && is_numeric($row)) {
                    return \sprintf('%s %d › %s', $label, (int) $row + 1, $this->translator->trans($this->registry->getFieldLabel($block, $subField, $field), [], $domain));
                }
            }

            return $label;
        }

        return (string) $name;
    }
}
