<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\Type\TitleTextType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Project extension of a bundle block (a form type extension): a subtitle after the title.
 */
final class SubtitleExtension extends AbstractTypeExtension
{
    public static function getExtendedTypes(): iterable
    {
        return [TitleTextType::class];
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $text = $builder->get('text');
        $builder
            ->remove('text')
            ->add('subtitle', TextType::class, ['label' => 'Sous-titre', 'required' => false])
            ->add($text);
    }
}
