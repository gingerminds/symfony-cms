<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Search;

/**
 * A searchable content that can be hidden from the search (its flag, its category...).
 */
interface ExcludableSearchableInterface extends PubliclySearchableInterface, ExcludableFromSearchInterface
{
}
