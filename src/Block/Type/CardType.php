<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A card of the CardsType block.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class CardType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['required' => false])
            ->add('description', TextareaType::class, ['required' => false, 'attr' => ['rows' => 4], 'size' => 'xl'])
            ->add('image', FilePickerType::class, ['constraints' => [new NotBlank()], 'as_id' => true, 'accept' => ['image/*']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label_format', 'block.cards.field.cards_%name%');
    }
}
