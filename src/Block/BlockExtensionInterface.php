<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

/**
 * Changes the fields of blocks without replacing them (like a form type extension): several
 * extensions can stack on one block, by tag priority (`gingerminds_cms.block_extension`,
 * autoconfigured; highest priority first).
 *
 *     final class SubtitleExtension implements BlockExtensionInterface
 *     {
 *         public static function getExtendedBlocks(): iterable
 *         {
 *             return [TitleText::getKey()];
 *         }
 *
 *         public function buildFields(array $fields, BlockInterface $block): array
 *         {
 *             return [...$fields, BlockField::text('subtitle')->label('block.subtitle')];
 *         }
 *     }
 */
interface BlockExtensionInterface
{
    public const string TAG = 'gingerminds_cms.block_extension';

    /**
     * @return iterable<string> keys of the extended blocks
     */
    public static function getExtendedBlocks(): iterable;

    /**
     * @param list<BlockField> $fields the fields of the block, previous extensions applied
     *
     * @return list<BlockField>
     */
    public function buildFields(array $fields, BlockInterface $block): array;
}
