<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A content of the public search (`gingerminds_cms.search.resources`): one entry per language,
 * its searchable text read from the configured `fields` of the entry subject.
 */
interface PubliclySearchableInterface
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
