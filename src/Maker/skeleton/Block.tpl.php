<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use Gingerminds\CmsBundle\Block\AsContentBlock;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Content block `<?= $key ?>`: its fields (form type), found by the CMS block registry.
 * Labels: `block.<?= $key ?>.label`, `block.<?= $key ?>.field.<name>` of the `messages` domain.
 *
 * @extends AbstractType<array<string, mixed>>
 */
// `key`: stored as `type` in the content, never change it once pages use the block.
#[AsContentBlock(key: '<?= $key ?>', preview: '<?= $preview_template ?>', icon: 'bi-square')]
class <?= $class_name ?> extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, ['constraints' => [new NotBlank()]]);
    }
}
