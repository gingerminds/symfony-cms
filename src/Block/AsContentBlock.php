<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

/**
 * A content block of the pages: a form type (its fields) with its picker and preview metadata,
 * found by BlockRegistry (autoconfigured). Read on the class or its parents: a project form type
 * extending a bundle block and replacing its service keeps it.
 *
 * - `key`: stored as `type` in the content, never change it once pages use the block;
 * - `preview`: the Twig template of the admin preview (`block`, `data`, `refs`, `uid`);
 * - `label`: translation key, `block.<key>.label` by default; the fields are labelled
 *   `block.<key>.field.<name>` (`label_format`);
 * - `order`: picker weight (ascending), overridden by `gingerminds_cms.blocks.order`.
 */
#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsContentBlock
{
    public function __construct(
        public string $key,
        public string $preview,
        public string $icon = 'bi-square',
        public int $order = 100,
        public ?string $label = null,
        public string $translationDomain = 'messages',
    ) {
    }
}
