<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MultisiteBundle\Entity\Language\LanguageInterface;

/**
 * Id and language of a translation of the bundle (TranslationInterface). The owner relation stays
 * declared on each translation: its target is an overridable interface (see MenuItemTranslation).
 */
trait TranslationFieldsTrait
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    protected ?int $id = null;

    #[ORM\ManyToOne(targetEntity: LanguageInterface::class)]
    #[ORM\JoinColumn(name: 'language_id', nullable: false, onDelete: 'CASCADE')]
    protected ?LanguageInterface $language = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLanguage(): ?LanguageInterface
    {
        return $this->language;
    }

    public function setLanguage(LanguageInterface $language): void
    {
        $this->language = $language;
    }

    public function isFor(LanguageInterface|int $language): bool
    {
        $id = $language instanceof LanguageInterface ? $language->getId() : $language;

        return $this->language === $language || (null !== $id && $this->language?->getId() === $id);
    }
}
