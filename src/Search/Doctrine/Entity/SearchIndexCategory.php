<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search\Doctrine\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * A category of an indexed content (`search_index_categories`, as Laravel): the category facets.
 * No foreign key to the categories: their table depends on the type of the content.
 */
#[ORM\Entity]
#[ORM\Table(name: 'search_index_categories')]
#[ORM\Index(name: 'idx_search_index_categories_category', columns: ['category_id'])]
class SearchIndexCategory
{
    public function __construct(
        #[ORM\Id]
        #[ORM\ManyToOne(targetEntity: SearchIndex::class)]
        #[ORM\JoinColumn(name: 'search_index_id', nullable: false, onDelete: 'CASCADE')]
        private SearchIndex $searchIndex,
        #[ORM\Id]
        #[ORM\Column(name: 'category_id', type: Types::BIGINT, options: ['unsigned' => true])]
        private string $categoryId,
    ) {
    }

    public function getSearchIndex(): SearchIndex
    {
        return $this->searchIndex;
    }

    public function getCategoryId(): int
    {
        return (int) $this->categoryId;
    }
}
