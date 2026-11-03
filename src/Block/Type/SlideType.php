<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A slide of the SliderType block.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class SlideType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('image', FilePickerType::class, ['constraints' => [new NotBlank()], 'as_id' => true, 'accept' => ['image/*']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label_format', 'block.slider.field.slides_%name%');
    }
}
