<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * An entity whose change reindexes a searchable content (its URLs...). A translation of a
 * searchable content reindexes it without this interface.
 */
interface SearchOwnedInterface
{
    public function getSearchOwner(): ?PubliclySearchableInterface;
}
