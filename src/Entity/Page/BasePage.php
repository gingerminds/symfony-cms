<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\PublishableTrait;
use Gingerminds\CmsBundle\Model\VisualsTrait;
use Gingerminds\CmsBundle\Search\ExcludableFromSearchInterface;
use Gingerminds\CmsBundle\Search\SearchIndex;
use Gingerminds\CmsBundle\Search\SearchIndexableInterface;
use Gingerminds\CmsBundle\Validator\SinglePageCategory;
use Gingerminds\CoreBundle\Model\Cacheable;
use Gingerminds\CoreBundle\Model\EagerLoad;
use Gingerminds\CoreBundle\Model\Filter;
use Gingerminds\CoreBundle\Model\Searchable;
use Gingerminds\CoreBundle\Model\Sortable;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
use Gingerminds\MultisiteBundle\Model\TranslationInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * A page (`pages`, as Laravel; `status`: a place of the publication workflow).
 * Its visuals are overridden per language by the translation ones.
 */
#[ORM\MappedSuperclass]
#[UniqueEntity(fields: ['code', 'site'], errorPath: 'code')]
#[SinglePageCategory]
#[Cacheable(key: 'page')]
#[SearchIndex(type: 'page', label: 'page.name_p', translationDomain: 'GingermindsCms', category: 'category', group: self::GROUP_LIST)]
abstract class BasePage implements PageInterface, SearchIndexableInterface, ExcludableFromSearchInterface
{
    use PageApiFieldsTrait;
    use PageSearchTrait;
    use PageTranslatedFieldsTrait;
    use PublishableTrait;
    use TimestampableTrait;
    use TranslatableTrait;
    use VisualsTrait;

    public const string GROUP_LIST = 'page:list';
    public const string GROUP_READ = 'page:read';

    /**
     * The `security` of the item operation by id (front preview): a published page is public, any
     * other status needs the `view pages` permission (PageVoter): 401 anonymous, 403 without it.
     * A project entity redeclaring `#[ApiResource]` reuses it:
     * `new Get(security: self::API_ITEM_SECURITY)`.
     */
    public const string API_ITEM_SECURITY = "object.isPublished() or is_granted('VIEW', object)";

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    #[Sortable]
    #[Searchable]
    protected ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'gingerminds_cms.page.no_site')]
    protected ?SiteInterface $site = null;

    #[ORM\ManyToOne(targetEntity: PageCategoryInterface::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    #[EagerLoad(paths: ['translations'])]
    #[Filter(label: 'page.field.category')]
    protected ?PageCategoryInterface $category = null;

    /**
     * TranslatableTrait's, redeclared to search the titles.
     *
     * @var Collection<int, TranslationInterface>
     */
    #[EagerLoad(paths: ['language'])]
    #[Searchable(paths: ['title'])]
    protected Collection $translations;

    #[ORM\Column(name: 'is_hidden_from_search', options: ['default' => false])]
    protected bool $hiddenFromSearch = false;

    public function __construct()
    {
        $this->translations = new ArrayCollection();
        $this->urls = new ArrayCollection();
    }

    public static function getTranslationEntityClass(): string
    {
        return PageTranslation::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): void
    {
        $this->code = $code;
    }

    public function getSite(): ?SiteInterface
    {
        return $this->site;
    }

    public function setSite(?SiteInterface $site): void
    {
        $this->site = $site;
    }

    public function getCategory(): ?PageCategoryInterface
    {
        return $this->category;
    }

    public function setCategory(?PageCategoryInterface $category): void
    {
        $this->category = $category;
    }

    public function isHiddenFromSearch(): bool
    {
        return $this->hiddenFromSearch;
    }

    public function setHiddenFromSearch(bool $hiddenFromSearch): void
    {
        $this->hiddenFromSearch = $hiddenFromSearch;
    }

    public function __toString(): string
    {
        return $this->getTitle() ?? (string) $this->code;
    }
}
