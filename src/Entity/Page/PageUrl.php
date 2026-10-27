<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Search\PubliclySearchableInterface;
use Gingerminds\CmsBundle\Search\SearchOwnedInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;

/**
 * The public path of a published page in one language (`page_urls`, as Laravel): one page per
 * site, language and path. Maintained by PageUrlSyncer.
 */
#[ORM\Entity]
#[ORM\Table(name: 'page_urls')]
#[ORM\UniqueConstraint(name: 'page_urls_page_id_language_id_unique', columns: ['page_id', 'language_id'])]
#[ORM\UniqueConstraint(name: 'page_urls_site_language_path_unique', columns: ['site_id', 'language_id', 'path'])]
class PageUrl implements TimestampableInterface, SearchOwnedInterface
{
    use TimestampableTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: PageInterface::class, inversedBy: 'urls')]
        #[ORM\JoinColumn(name: 'page_id', nullable: false, onDelete: 'CASCADE')]
        private PageInterface $page,
        #[ORM\ManyToOne(targetEntity: LanguageInterface::class)]
        #[ORM\JoinColumn(name: 'language_id', nullable: false, onDelete: 'CASCADE')]
        private LanguageInterface $language,
        #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
        #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
        private SiteInterface $site,
        #[ORM\Column(length: 255)]
        private string $path,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPage(): PageInterface
    {
        return $this->page;
    }

    public function getSearchOwner(): ?PubliclySearchableInterface
    {
        return $this->page instanceof PubliclySearchableInterface ? $this->page : null;
    }

    public function getLanguage(): LanguageInterface
    {
        return $this->language;
    }

    public function getSite(): SiteInterface
    {
        return $this->site;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }
}
