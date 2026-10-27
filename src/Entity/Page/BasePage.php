<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\PublicationStatus;
use Gingerminds\CmsBundle\Model\PublishableTrait;
use Gingerminds\CmsBundle\Model\VisualsTrait;
use Gingerminds\CmsBundle\Search\ExcludableSearchableInterface;
use Gingerminds\CmsBundle\Validator\SinglePageCategory;
use Gingerminds\CoreBundle\Model\CacheableResourceInterface;
use Gingerminds\CoreBundle\Model\EagerLoadableInterface;
use Gingerminds\CoreBundle\Model\FilterableInterface;
use Gingerminds\CoreBundle\Model\SearchableInterface;
use Gingerminds\CoreBundle\Model\SortableInterface;
use Gingerminds\CoreBundle\Model\Trait\CacheableResourceTrait;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslatableTrait;
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
abstract class BasePage implements PageInterface, SortableInterface, SearchableInterface, FilterableInterface, EagerLoadableInterface, CacheableResourceInterface, ExcludableSearchableInterface
{
    use CacheableResourceTrait;
    use PageApiFieldsTrait;
    use PageSearchTrait;
    use PageTranslatedFieldsTrait;
    use PublishableTrait;
    use TimestampableTrait;
    use TranslatableTrait;
    use VisualsTrait;

    public const string GROUP_LIST = 'page:list';
    public const string GROUP_READ = 'page:read';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    #[Groups([self::GROUP_LIST, self::GROUP_READ])]
    protected ?string $code = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    #[Assert\NotNull(message: 'gingerminds_cms.page.no_site')]
    protected ?SiteInterface $site = null;

    #[ORM\ManyToOne(targetEntity: PageCategoryInterface::class)]
    #[ORM\JoinColumn(name: 'category_id', nullable: true, onDelete: 'SET NULL')]
    protected ?PageCategoryInterface $category = null;

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

    public static function getEagerLoads(): array
    {
        return [...self::getTranslationEagerLoads(), 'category', 'category.translations', 'urls', 'urls.language'];
    }

    public static function getCacheKey(): string
    {
        return 'page';
    }

    public static function getSearchableFields(): array
    {
        return ['code', 'translations.title'];
    }

    /**
     * The status choices are the places of the bundle workflow: PageController adds the project ones.
     */
    public static function getFilters(): array
    {
        return [
            'publishedAt' => ['type' => 'date', 'label' => 'page.field.published_at'],
            'status' => [
                'type' => 'select',
                'label' => 'page.field.status',
                'choices' => PublicationStatus::labels(),
                'multiple' => true,
            ],
            'category' => ['type' => 'select-entity', 'label' => 'page.field.category', 'entity' => PageCategoryInterface::class],
        ];
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
