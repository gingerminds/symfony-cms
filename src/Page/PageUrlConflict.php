<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Page;

use Gingerminds\CmsBundle\Entity\Page\PageInterface;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * The new path of a page in a language is taken by another published page: the page keeps its
 * former path (PageUrlSyncer).
 */
final readonly class PageUrlConflict
{
    public function __construct(
        public PageInterface $page,
        public LanguageInterface $language,
        public string $path,
        public PageInterface $owner,
    ) {
    }
}
