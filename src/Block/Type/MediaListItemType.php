<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\MediaManagerBundle\Form\Media\MediaSelectType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A media of the MediaListType block.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class MediaListItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('media', MediaSelectType::class, ['constraints' => [new NotBlank()], 'as_id' => true])
            ->add('subtitle', TextType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label_format', 'block.media_list.field.items_%name%');
    }
}
