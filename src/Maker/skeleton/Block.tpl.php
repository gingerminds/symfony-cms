<?= "<?php\n" ?>

declare(strict_types=1);

namespace <?= $namespace ?>;

use Gingerminds\CmsBundle\Block\AbstractBlock;
use Gingerminds\CmsBundle\Block\BlockField;

/**
 * Content block `<?= $key ?>`, found by the CMS block registry (autoconfigured service).
 * Labels: `block.<?= $key ?>.label`, `block.<?= $key ?>.field.<name>` of the `messages` domain.
 */
class <?= $class_name ?> extends AbstractBlock
{
    /**
     * Stored as `type` in the content: never change it once pages use the block.
     */
    public static function getKey(): string
    {
        return '<?= $key ?>';
    }

    public function getIcon(): string
    {
        return 'bi-square';
    }

    public function getFields(): array
    {
        return [
            BlockField::text('title')->required(),
        ];
    }

    public function getPreviewTemplate(): string
    {
        return '<?= $preview_template ?>';
    }

    public function getTranslationDomain(): string
    {
        return 'messages';
    }
}
