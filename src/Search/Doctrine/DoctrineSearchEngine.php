<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Doctrine;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\DBAL\Types\Types;
use Gingerminds\CmsBundle\Search\SearchDocument;
use Gingerminds\CmsBundle\Search\SearchEngineInterface;
use Gingerminds\CmsBundle\Search\SearchHit;
use Gingerminds\CmsBundle\Search\SearchQuery;
use Gingerminds\CmsBundle\Search\SearchResult;

/**
 * The `doctrine` search engine: the `search_index` / `search_index_categories` tables (as Laravel),
 * a `LIKE` on the text of every field (the weights are ignored), the facets by `GROUP BY`. Written
 * with DBAL: indexing runs after a flush.
 */
class DoctrineSearchEngine implements SearchEngineInterface
{
    private const string INDEX = 'search_index';
    private const string CATEGORIES = 'search_index_categories';

    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function index(string $type, int $recordId, array $documents): void
    {
        $this->connection->transactional(function () use ($type, $recordId, $documents): void {
            $existing = $this->existingRows($type, $recordId);
            $kept = [];

            foreach ($documents as $document) {
                $id = $existing[$document->languageId] ?? null;
                $id = null === $id ? $this->insert($document) : $this->update($id, $document);
                $kept[] = $id;
                $this->syncCategories($id, $document->categoryIds);
            }

            $this->deleteRows(array_values(array_diff($existing, $kept)));
        });
    }

    public function remove(string $type, int $recordId): void
    {
        $this->deleteRows(array_values($this->existingRows($type, $recordId)));
    }

    public function clear(?string $type = null): void
    {
        $qb = $this->connection->createQueryBuilder()->select('s.id')->from(self::INDEX, 's');

        if (null !== $type) {
            $qb->where('s.type = :type')->setParameter('type', $type);
        }

        $this->deleteRows(array_values(array_map(intval(...), $qb->fetchFirstColumn())));
    }

    public function search(SearchQuery $query): SearchResult
    {
        if ([] === $query->languageIds) {
            return new SearchResult([], 0);
        }

        $hits = $this->filtered($query, true)
            ->select('s.type', 's.record_id', 's.language_id', 's.title', 's.url', 's.excerpt', 's.published_at')
            ->orderBy('s.published_at', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setFirstResult($query->offset())
            ->setMaxResults($query->itemsPerPage)
            ->fetchAllAssociative();

        return new SearchResult(
            array_values(array_map($this->hit(...), $hits)),
            (int) $this->filtered($query, true)->select('COUNT(s.id)')->fetchOne(),
            $this->typeCounts($query),
            $this->categoryCounts($query),
        );
    }

    /**
     * @return array<string, int>
     */
    private function typeCounts(SearchQuery $query): array
    {
        $counts = [];

        foreach ($this->filtered($query, false)->select('s.type', 'COUNT(s.id) AS total')->groupBy('s.type')->fetchAllAssociative() as $row) {
            $counts[(string) $row['type']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * @return array<string, array<int, int>>
     */
    private function categoryCounts(SearchQuery $query): array
    {
        $rows = $this->filtered($query, false)
            ->select('s.type', 'c.category_id', 'COUNT(s.id) AS total')
            ->innerJoin('s', self::CATEGORIES, 'c', 'c.search_index_id = s.id')
            ->groupBy('s.type', 'c.category_id')
            ->fetchAllAssociative();
        $counts = [];

        foreach ($rows as $row) {
            $counts[(string) $row['type']][(int) $row['category_id']] = (int) $row['total'];
        }

        return $counts;
    }

    /**
     * The rows of the query: site, languages (one version per content), text, and the type filter
     * unless counting the facets.
     */
    private function filtered(SearchQuery $query, bool $withTypes): QueryBuilder
    {
        $qb = $this->connection->createQueryBuilder()->from(self::INDEX, 's');

        if (null !== $query->siteId) {
            $qb->andWhere('s.site_id = :site')->setParameter('site', $query->siteId, ParameterType::INTEGER);
        }

        $this->filterLanguages($qb, $query->languageIds);

        if (null !== $query->text && '' !== trim($query->text)) {
            $qb->andWhere('s.searchable_text LIKE :text')->setParameter('text', '%' . addcslashes(trim($query->text), '%_\\') . '%');
        }

        if ($withTypes && $query->filtersTypes()) {
            $this->filterTypes($qb, $query);
        }

        return $qb;
    }

    /**
     * A row in a language when the content has none in the languages before it.
     *
     * @param list<int> $languageIds
     */
    private function filterLanguages(QueryBuilder $qb, array $languageIds): void
    {
        $conditions = [];

        foreach ($languageIds as $index => $languageId) {
            $condition = 's.language_id = :language' . $index;
            $qb->setParameter('language' . $index, $languageId, ParameterType::INTEGER);

            if ($index > 0) {
                $qb->setParameter('before' . $index, \array_slice($languageIds, 0, $index), ArrayParameterType::INTEGER);
                $condition .= \sprintf(
                    ' AND NOT EXISTS (SELECT 1 FROM %s p%2$d WHERE p%2$d.type = s.type AND p%2$d.record_id = s.record_id AND p%2$d.language_id IN (:before%2$d))',
                    self::INDEX,
                    $index,
                );
            }

            $conditions[] = '(' . $condition . ')';
        }

        $qb->andWhere(implode(' OR ', $conditions));
    }

    /**
     * Whole types OR (type, categories): `filters[type]=page&filters[type]=news:12`.
     */
    private function filterTypes(QueryBuilder $qb, SearchQuery $query): void
    {
        $conditions = [];

        if ([] !== $query->types) {
            $conditions[] = 's.type IN (:types)';
            $qb->setParameter('types', $query->types, ArrayParameterType::STRING);
        }

        $index = 0;

        foreach ($query->categories as $type => $categoryIds) {
            $conditions[] = \sprintf(
                '(s.type = :ctype%1$d AND s.id IN (SELECT ci%1$d.search_index_id FROM %2$s ci%1$d WHERE ci%1$d.category_id IN (:cids%1$d)))',
                $index,
                self::CATEGORIES,
            );
            $qb->setParameter('ctype' . $index, $type)->setParameter('cids' . $index, $categoryIds, ArrayParameterType::INTEGER);
            ++$index;
        }

        $qb->andWhere(implode(' OR ', $conditions));
    }

    /**
     * @return array<int, int> language id => row id
     */
    private function existingRows(string $type, int $recordId): array
    {
        $rows = $this->connection->createQueryBuilder()
            ->select('s.id', 's.language_id')
            ->from(self::INDEX, 's')
            ->where('s.type = :type')
            ->andWhere('s.record_id = :record')
            ->setParameter('type', $type)
            ->setParameter('record', $recordId, ParameterType::INTEGER)
            ->fetchAllAssociative();
        $existing = [];

        foreach ($rows as $row) {
            $existing[(int) $row['language_id']] = (int) $row['id'];
        }

        return $existing;
    }

    private function insert(SearchDocument $document): int
    {
        $now = new \DateTimeImmutable();
        $this->connection->insert(self::INDEX, [
            'type' => $document->type,
            'record_id' => $document->recordId,
            'language_id' => $document->languageId,
            'created_at' => $now,
            ...$this->values($document, $now),
        ], $this->types());

        return (int) $this->connection->lastInsertId();
    }

    private function update(int $id, SearchDocument $document): int
    {
        $this->connection->update(self::INDEX, $this->values($document, new \DateTimeImmutable()), ['id' => $id], $this->types());

        return $id;
    }

    /**
     * @return array<string, mixed>
     */
    private function values(SearchDocument $document, \DateTimeImmutable $now): array
    {
        return [
            'site_id' => $document->siteId,
            'title' => mb_substr($document->title, 0, 255),
            'url' => mb_substr($document->url, 0, 255),
            'excerpt' => $document->excerpt,
            'searchable_text' => $document->text(),
            'published_at' => $document->publishedAt,
            'updated_at' => $now,
        ];
    }

    /**
     * @return array<string, string>
     */
    private function types(): array
    {
        return ['published_at' => Types::DATETIME_IMMUTABLE, 'created_at' => Types::DATETIME_IMMUTABLE, 'updated_at' => Types::DATETIME_IMMUTABLE];
    }

    /**
     * @param list<int> $categoryIds
     */
    private function syncCategories(int $id, array $categoryIds): void
    {
        $this->connection->delete(self::CATEGORIES, ['search_index_id' => $id]);

        foreach ($categoryIds as $categoryId) {
            $this->connection->insert(self::CATEGORIES, ['search_index_id' => $id, 'category_id' => $categoryId]);
        }
    }

    /**
     * Rows and their categories (no foreign key cascade on every platform).
     *
     * @param list<int> $ids
     */
    private function deleteRows(array $ids): void
    {
        foreach (array_chunk($ids, 500) as $chunk) {
            $this->connection->executeStatement('DELETE FROM ' . self::CATEGORIES . ' WHERE search_index_id IN (?)', [$chunk], [ArrayParameterType::INTEGER]);
            $this->connection->executeStatement('DELETE FROM ' . self::INDEX . ' WHERE id IN (?)', [$chunk], [ArrayParameterType::INTEGER]);
        }
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hit(array $row): SearchHit
    {
        $publishedAt = $row['published_at'] ?? null;

        return new SearchHit(
            (string) $row['type'],
            (int) $row['record_id'],
            (int) $row['language_id'],
            (string) $row['title'],
            (string) $row['url'],
            null === $row['excerpt'] ? null : (string) $row['excerpt'],
            null === $publishedAt ? null : $this->connection->convertToPHPValue($publishedAt, Types::DATETIME_IMMUTABLE),
        );
    }
}
