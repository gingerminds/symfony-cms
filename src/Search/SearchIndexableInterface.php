<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content of the public search index (its type: #[SearchIndex]): one entry per language, with
 * the title, URL, excerpt and searched texts. ExcludableFromSearchInterface when it can be hidden.
 */
interface SearchIndexableInterface
{
    /**
     * Whether the content may be found (published...), whatever its language.
     */
    public function isPubliclyVisible(): bool;

    /**
     * One entry per language the content has (an incomplete translation is skipped).
     *
     * @return iterable<SearchEntry>
     */
    public function getSearchEntries(): iterable;
}
