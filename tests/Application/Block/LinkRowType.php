<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A row of LinksBlockType.
 *
 * @extends AbstractType<array<string, mixed>>
 */
final class LinkRowType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, ['label' => 'Libellé', 'constraints' => [new NotBlank()]])
            ->add('url', TextType::class, ['label' => 'URL', 'constraints' => [new NotBlank()]]);
    }
}
