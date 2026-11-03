<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Type;

use Gingerminds\CmsBundle\Validator\LinkUrl;
use Gingerminds\MediaManagerBundle\Form\File\FilePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * A link of the LinkListType block.
 *
 * @extends AbstractType<array<string, mixed>>
 */
class LinkType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, ['constraints' => [new NotBlank()]])
            ->add('url', TextType::class, ['constraints' => [new NotBlank(), new LinkUrl()]])
            ->add('image', FilePickerType::class, ['required' => false, 'as_id' => true, 'accept' => ['image/*']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefault('label_format', 'block.link_list.field.links_%name%');
    }
}
