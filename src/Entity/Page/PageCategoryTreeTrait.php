<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Page;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * The place of a page category in the tree of its site. Both sides of the relations are kept in
 * sync. Deleting a category moves its children to the root.
 */
trait PageCategoryTreeTrait
{
    #[ORM\ManyToOne(targetEntity: PageCategoryInterface::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', nullable: true, onDelete: 'SET NULL')]
    protected ?PageCategoryInterface $parent = null;

    /**
     * @var Collection<int, PageCategoryInterface>
     */
    #[ORM\OneToMany(targetEntity: PageCategoryInterface::class, mappedBy: 'parent')]
    #[ORM\OrderBy(['position' => 'ASC', 'code' => 'ASC'])]
    protected Collection $children;

    public function getParent(): ?PageCategoryInterface
    {
        return $this->parent;
    }

    public function setParent(?PageCategoryInterface $parent): void
    {
        $this->parent?->getChildren()->removeElement($this);
        $this->parent = $parent;

        if ($parent instanceof PageCategoryInterface && !$parent->getChildren()->contains($this)) {
            $parent->getChildren()->add($this);
        }
    }

    public function getChildren(): Collection
    {
        return $this->children;
    }

    /**
     * The parent belongs to the same site, is neither the category nor one of its descendants,
     * and has a prefix in each of its languages (its children would go up in the URLs otherwise).
     */
    #[Assert\Callback]
    public function validateParent(ExecutionContextInterface $context): void
    {
        $parent = $this->parent;

        if (!$parent instanceof PageCategoryInterface) {
            return;
        }

        if ($parent->getSite() !== $this->getSite()) {
            $context->buildViolation('gingerminds_cms.page_category.parent_other_site')->atPath('parent')->addViolation();

            return;
        }

        for ($ancestor = $parent; $ancestor instanceof PageCategoryInterface; $ancestor = $ancestor->getParent()) {
            if ($ancestor === $this) {
                $context->buildViolation('gingerminds_cms.page_category.parent_cycle')->atPath('parent')->addViolation();

                return;
            }
        }

        $languages = [];

        foreach ($parent->getTranslations() as $translation) {
            if (null === $parent->getPrefix($translation->getLanguage()?->getId())) {
                $languages[] = (string) $translation->getLanguage()?->getIso();
            }
        }

        if ([] !== $languages) {
            $context->buildViolation('gingerminds_cms.page_category.parent_without_prefix')
                ->setParameter('{{ languages }}', implode(', ', $languages))
                ->atPath('parent')
                ->addViolation();
        }
    }
}
