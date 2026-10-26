<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block\Reference;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Block\BlockField;
use Gingerminds\MediaManagerBundle\Entity\Media\BaseMedia;
use Gingerminds\MediaManagerBundle\Entity\Media\MediaInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * `media` fields: a media as in `GET /api/media` (`media:list` group, the project media included),
 * loaded with its files and category.
 */
final readonly class MediaReferenceResolver implements BlockReferenceResolverInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private NormalizerInterface $normalizer,
    ) {
    }

    public static function getFieldType(): string
    {
        return BlockField::MEDIA;
    }

    public function load(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map(intval(...), array_filter($ids, is_numeric(...))))));

        if ([] === $ids) {
            return [];
        }

        /** @var list<MediaInterface> $medias */
        $medias = $this->entityManager->createQueryBuilder()
            ->select('m', 'f', 't', 'c')
            ->from(MediaInterface::class, 'm')
            ->leftJoin('m.file', 'f')
            ->leftJoin('m.thumbnail', 't')
            ->leftJoin('m.category', 'c')
            ->where('m.id IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult();
        $loaded = [];

        foreach ($medias as $media) {
            $loaded[(string) $media->getId()] = $media;
        }

        return $loaded;
    }

    public function normalize(object $entity): array
    {
        if (!$entity instanceof MediaInterface) {
            throw new \InvalidArgumentException(\sprintf('A "%s" is expected.', MediaInterface::class));
        }

        /** @var array<string, mixed> */
        return $this->normalizer->normalize($entity, 'json', ['groups' => [BaseMedia::GROUP_LIST]]);
    }
}
