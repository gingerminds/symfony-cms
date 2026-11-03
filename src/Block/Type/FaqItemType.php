<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Form\Type\WysiwygType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A question of the FaqType block.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class FaqItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('question', TextType::class, ['constraints' => [new NotBlank()], 'size' => 'xl'])
            ->add('answer', WysiwygType::class, ['constraints' => [new NotBlank()], 'rows' => 8, 'size' => 'xl']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label_format', 'block.faq.field.items_%name%');
    }
}
