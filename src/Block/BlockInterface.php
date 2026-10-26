<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

/**
 * A content block of the pages (`PageTranslation::content`: `[{uid, type, data}]`, `type` being
 * the block key). Its service is tagged `gingerminds_cms.block` (autoconfigured): BlockRegistry
 * finds it, no registration needed. A block extending another one with the same key replaces it
 * (project override); BlockExtensionInterface services change the fields of a block.
 */
interface BlockInterface
{
    public const string TAG = 'gingerminds_cms.block';

    /**
     * Stable identifier, stored as `type` in the content: never change it once pages use the block.
     */
    public static function getKey(): string;

    /**
     * Name in the block picker (translation key of getTranslationDomain()).
     */
    public function getLabel(): string;

    /**
     * Bootstrap Icons class (e.g. `bi-type`).
     */
    public function getIcon(): string;

    /**
     * Picker weight (ascending), overridden by `gingerminds_cms.blocks.order`.
     */
    public function getOrder(): int;

    /**
     * The fields, in display order. Read them with BlockRegistry::getFields() (extensions applied).
     *
     * @return list<BlockField>
     */
    public function getFields(): array;

    /**
     * Twig template of the admin preview: variables `block`, `data` (with the field defaults), `uid`.
     */
    public function getPreviewTemplate(): string;

    /**
     * Translation domain of the labels.
     */
    public function getTranslationDomain(): string;
}
