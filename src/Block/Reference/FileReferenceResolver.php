<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\MediaManagerBundle\Entity\File\FileInterface;
use Gingerminds\MediaManagerBundle\File\FileLibraryPresenter;
use Gingerminds\MediaManagerBundle\Twig\MediaManagerExtension;
use Symfony\Component\Uid\Uuid;

/**
 * `file` fields: `{id, url, thumbnail_url, mime_type, original_name, size, is_image}` (as Laravel),
 * `thumbnail_url` being the `thumbnail` preset of an image (null otherwise, or without the preset).
 */
final readonly class FileReferenceResolver implements BlockReferenceResolverInterface
{
    /**
     * @param array<string, mixed> $presets the image presets of the media manager
     */
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MediaManagerExtension $urls,
        private array $presets,
    ) {
    }

    public static function getFieldType(): string
    {
        return BlockField::FILE;
    }

    public function load(array $ids): array
    {
        $ids = array_values(array_filter(array_map(strval(...), $ids), Uuid::isValid(...)));
        $files = [];

        foreach ([] === $ids ? [] : $this->entityManager->getRepository(FileInterface::class)->findBy(['id' => $ids]) as $file) {
            $files[$file->getId()] = $file;
        }

        return $files;
    }

    public function normalize(object $entity): array
    {
        if (!$entity instanceof FileInterface) {
            throw new \InvalidArgumentException(\sprintf('A "%s" is expected.', FileInterface::class));
        }

        $thumbnail = $entity->isImage() && isset($this->presets[FileLibraryPresenter::THUMBNAIL_PRESET]);

        return [
            'id' => $entity->getId(),
            'url' => $this->urls->fileUrl($entity),
            'thumbnail_url' => $thumbnail ? $this->urls->fileUrl($entity, FileLibraryPresenter::THUMBNAIL_PRESET) : null,
            'mime_type' => $entity->getMimeType(),
            'original_name' => $entity->getOriginalName(),
            'size' => $entity->getSize(),
            'is_image' => $entity->isImage(),
        ];
    }
}
