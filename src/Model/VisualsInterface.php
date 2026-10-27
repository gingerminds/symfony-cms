<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Model;

use Gingerminds\MediaManagerBundle\Entity\File\FileInterface;

/**
 * A main visual and a thumbnail picked in the file library (VisualsTrait).
 */
interface VisualsInterface
{
    public function getMainVisual(): ?FileInterface;

    public function setMainVisual(?FileInterface $mainVisual): void;

    public function getThumbnail(): ?FileInterface;

    public function setThumbnail(?FileInterface $thumbnail): void;
}
