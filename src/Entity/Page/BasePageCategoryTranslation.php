<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\CmsBundle\Model\TranslationSiteTrait;
use Gingerminds\CmsBundle\Validator\UniqueSiblingPrefix;
use Gingerminds\CoreBundle\Model\CacheCascade;
use Gingerminds\CoreBundle\Model\Trait\TimestampableTrait;
use Gingerminds\MultisiteBundle\Entity\Site\SiteInterface;
use Gingerminds\MultisiteBundle\Model\TranslationTrait;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Name and URL prefix of a page category in one language. `site_id` copies the one of the
 * category (as Laravel). The owner relation (`page_category_id`) is mapped by multisite, on the
 * overridable PageCategoryInterface.
 */
#[ORM\MappedSuperclass]
#[ORM\HasLifecycleCallbacks]
#[UniqueSiblingPrefix]
#[CacheCascade(['page_category', 'page'])]
abstract class BasePageCategoryTranslation implements PageCategoryTranslationInterface
{
    use TimestampableTrait;
    use TranslationSiteTrait;
    use TranslationTrait;

    public const string PREFIX_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SiteInterface::class)]
    #[ORM\JoinColumn(name: 'site_id', nullable: false, onDelete: 'CASCADE')]
    protected ?SiteInterface $site = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    protected ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(max: 255)]
    #[Assert\Regex(pattern: self::PREFIX_PATTERN, message: 'gingerminds_cms.page_category.prefix_format')]
    protected ?string $prefix = null;

    public static function getTranslatableEntityClass(): string
    {
        return PageCategoryInterface::class;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): void
    {
        $this->name = $name;
    }

    public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    /**
     * A blank prefix is stored as null (the category adds no segment to the page URLs).
     */
    public function setPrefix(?string $prefix): void
    {
        $prefix = null !== $prefix ? trim($prefix) : null;
        $this->prefix = '' === $prefix ? null : $prefix;
    }

    /**
     * A category with child categories needs a prefix (they would go up in the URLs otherwise).
     */
    #[Assert\Callback]
    public function validatePrefixOfAParent(ExecutionContextInterface $context): void
    {
        if (null === $this->prefix && $this->translatable instanceof PageCategoryInterface && !$this->translatable->getChildren()->isEmpty()) {
            $context->buildViolation('gingerminds_cms.page_category.prefix_required_with_children')->atPath('prefix')->addViolation();
        }
    }
}
