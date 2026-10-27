<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content (or a category of contents) hidden from the public search, by its own flag or by an
 * ancestor. A category change of `hiddenFromSearch` or `parent` reindexes its contents.
 */
interface ExcludableFromSearchInterface
{
    public function isExcludedFromSearch(): bool;
}
