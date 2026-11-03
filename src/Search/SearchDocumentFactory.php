<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

use Gingerminds\CmsBundle\Model\PublishableInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * The documents of an indexed content: one per entry (language), its title and texts as plain
 * text, its categories (the `category` association of its #[SearchIndex]).
 */
class SearchDocumentFactory
{
    public function __construct(
        private readonly PropertyAccessorInterface $propertyAccessor,
    ) {
    }

    /**
     * @return list<SearchDocument>
     */
    public function documents(SearchResource $resource, SearchIndexableInterface $entity): array
    {
        $recordId = self::id($entity);

        if (null === $recordId) {
            return [];
        }

        $siteId = $entity instanceof SiteContextedInterface ? $entity->getSite()?->getId() : null;
        $publishedAt = $entity instanceof PublishableInterface ? $entity->getPublishedAt() : null;
        $categoryIds = $this->categoryIds($resource, $entity);
        $documents = [];

        foreach ($entity->getSearchEntries() as $entry) {
            $languageId = $entry->language->getId();

            if (null === $languageId || '' === trim($entry->title)) {
                continue;
            }

            $documents[] = new SearchDocument(
                $resource->type,
                $recordId,
                $siteId,
                $languageId,
                $entry->title,
                $entry->url,
                $entry->excerpt,
                implode(' ', array_filter(array_map(self::plainText(...), [$entry->title, ...$entry->texts]), static fn (string $text): bool => '' !== $text)),
                $publishedAt,
                $categoryIds,
            );
        }

        return $documents;
    }

    /**
     * The integer id of an entity, null without one.
     */
    public static function id(object $entity): ?int
    {
        $id = method_exists($entity, 'getId') ? $entity->getId() : null;

        return \is_int($id) || (\is_string($id) && ctype_digit($id)) ? (int) $id : null;
    }

    /**
     * Text without markup, its whitespace collapsed.
     */
    public static function plainText(mixed $value): string
    {
        $text = \is_scalar($value) || $value instanceof \Stringable ? (string) $value : '';
        $text = html_entity_decode(strip_tags(str_replace('<', ' <', $text)), \ENT_QUOTES | \ENT_HTML5);

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /**
     * @return list<int>
     */
    private function categoryIds(SearchResource $resource, object $entity): array
    {
        if (null === $resource->category || !$this->propertyAccessor->isReadable($entity, $resource->category)) {
            return [];
        }

        $categories = $this->propertyAccessor->getValue($entity, $resource->category);
        $ids = [];

        foreach (is_iterable($categories) ? $categories : [$categories] as $category) {
            $id = \is_object($category) ? self::id($category) : null;

            if (null !== $id) {
                $ids[] = $id;
            }
        }

        return array_values(array_unique($ids));
    }
}
