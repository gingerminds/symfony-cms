<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\ApiPlatform\Serializer;

use Gingerminds\CmsBundle\Block\Reference\ContentReferenceResolver;
use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerAwareTrait;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * The `content` of a page with its references resolved (ContentReferenceResolver): the files and
 * medias of the blocks are served as objects, the stored content keeps their ids.
 */
final class PageContentNormalizer implements NormalizerInterface, NormalizerAwareInterface
{
    use NormalizerAwareTrait;

    private const string ALREADY_CALLED = 'gingerminds_cms_page_content_normalizer';

    public function __construct(
        private readonly ContentReferenceResolver $references,
    ) {
    }

    /**
     * @param array<string, mixed> $context
     *
     * @return array<mixed>|string|int|float|bool|\ArrayObject<array-key, mixed>|null
     */
    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        $normalized = $this->normalizer->normalize($data, $format, [...$context, self::ALREADY_CALLED => true]);

        if (\is_array($normalized) && \is_array($normalized['content'] ?? null)) {
            $normalized['content'] = $this->references->resolve(array_values($normalized['content']));
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $context
     */
    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof PageInterface && !isset($context[self::ALREADY_CALLED]);
    }

    public function getSupportedTypes(?string $format): array
    {
        return [PageInterface::class => false];
    }
}
