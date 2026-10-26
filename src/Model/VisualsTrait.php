<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Doctrine\ORM\Mapping as ORM;
use Gingerminds\MediaManagerBundle\Entity\File\FileInterface;

/**
 * Implements VisualsInterface: `main_visual_id`, `thumbnail_id` (RESTRICT: a used file is kept,
 * see the media manager file references).
 */
trait VisualsTrait
{
    #[ORM\ManyToOne(targetEntity: FileInterface::class)]
    #[ORM\JoinColumn(name: 'main_visual_id', nullable: true, onDelete: 'RESTRICT')]
    protected ?FileInterface $mainVisual = null;

    #[ORM\ManyToOne(targetEntity: FileInterface::class)]
    #[ORM\JoinColumn(name: 'thumbnail_id', nullable: true, onDelete: 'RESTRICT')]
    protected ?FileInterface $thumbnail = null;

    public function getMainVisual(): ?FileInterface
    {
        return $this->mainVisual;
    }

    public function setMainVisual(?FileInterface $mainVisual): void
    {
        $this->mainVisual = $mainVisual;
    }

    public function getThumbnail(): ?FileInterface
    {
        return $this->thumbnail;
    }

    public function setThumbnail(?FileInterface $thumbnail): void
    {
        $this->thumbnail = $thumbnail;
    }
}
