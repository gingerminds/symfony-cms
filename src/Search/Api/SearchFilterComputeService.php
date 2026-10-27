<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Api;

use Doctrine\ORM\EntityManagerInterface;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResource;
use Gingerminds\CmsBundle\Search\SearchResourceRegistry;
use Gingerminds\CmsBundle\Search\SearchResult;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * The `type` facet of /search-results (Laravel SearchFilterComputeService): each type with hits,
 * then its categories with hits (`type:id`, `group`: the type), the selected ones kept at 0; counted
 * against the text, without the type filter.
 */
class SearchFilterComputeService
{
    public function __construct(
        private readonly SearchResourceRegistry $resources,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array{type: array{type: string, multiple: bool, options: list<array{value: string, label: string, total: int, group: ?string}>}}
     */
    public function compute(SearchQuery $query, SearchResult $result, ?string $locale): array
    {
        $options = [];

        foreach ($this->resources->all() as $type => $resource) {
            $total = $result->typeCounts[$type] ?? 0;

            if ($total > 0 || \in_array($type, $query->types, true)) {
                $label = null !== $resource->label ? $this->translator->trans($resource->label, [], $resource->translationDomain, $locale) : $type;
                $options[] = ['value' => $type, 'label' => $label, 'total' => $total, 'group' => null];
            }

            $counts = $result->categoryCounts[$type] ?? [];

            foreach ($query->categories[$type] ?? [] as $categoryId) {
                $counts[$categoryId] ??= 0;
            }

            $options = [...$options, ...$this->categoryOptions($resource, $counts)];
        }

        return ['type' => ['type' => 'facet', 'multiple' => true, 'options' => $options]];
    }

    /**
     * @param array<int, int> $counts category id => hits
     *
     * @return list<array{value: string, label: string, total: int, group: string}>
     */
    private function categoryOptions(SearchResource $resource, array $counts): array
    {
        if ([] === $counts || null === $resource->category) {
            return [];
        }

        $labels = $this->categoryLabels($resource, array_keys($counts));
        $options = [];

        foreach ($counts as $categoryId => $total) {
            $options[] = [
                'value' => $resource->type . ':' . $categoryId,
                'label' => $labels[$categoryId] ?? (string) $categoryId,
                'total' => $total,
                'group' => $resource->type,
            ];
        }

        return $options;
    }

    /**
     * @param list<int> $ids
     *
     * @return array<int, string> the categories (in the current language) by id
     */
    private function categoryLabels(SearchResource $resource, array $ids): array
    {
        $metadata = $this->entityManager->getClassMetadata($resource->entity);

        if (null === $resource->category || !$metadata->hasAssociation($resource->category)) {
            return [];
        }

        $labels = [];

        foreach ($this->entityManager->getRepository($metadata->getAssociationTargetClass($resource->category))->findBy(['id' => $ids]) as $category) {
            $id = method_exists($category, 'getId') ? $category->getId() : null;

            if (\is_int($id) && $category instanceof \Stringable) {
                $labels[$id] = (string) $category;
            }
        }

        return $labels;
    }
}
