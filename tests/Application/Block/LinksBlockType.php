<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Tests\Application\Block;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Gingerminds\CmsBundle\Form\Block\BlockCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Count;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Project block of the tests: a select with a default, a switch, required rows.
 *
 * @extends AbstractType<array<string, mixed>>
 */
#[AsContentBlock(key: 'test_links', preview: 'block/test_links.html.twig', icon: 'bi-link', order: 5, label: 'Liens')]
final class LinksBlockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('heading', TextType::class, ['label' => 'Intitulé', 'constraints' => [new NotBlank()]])
            ->add('style', ChoiceType::class, ['label' => 'Style', 'choices' => ['Clair' => 'light', 'Sombre' => 'dark'], 'required' => false, 'placeholder' => '', 'empty_data' => 'light'])
            ->add('highlighted', CheckboxType::class, ['label' => 'Mis en avant', 'required' => false])
            ->add('links', BlockCollectionType::class, [
                'label' => 'Liens',
                'entry_type' => LinkRowType::class,
                'constraints' => [new Count(min: 1)],
                'add_label' => 'Ajouter un lien',
                'item_label' => 'Lien',
            ]);
    }
}
