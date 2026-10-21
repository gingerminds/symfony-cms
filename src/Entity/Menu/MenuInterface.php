<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Entity\Menu;

use Gingerminds\CoreBundle\Model\ResourceInterface;
use Gingerminds\CoreBundle\Model\TimestampableInterface;
use Gingerminds\MultisiteBundle\Model\SiteContextedInterface;

interface MenuInterface extends ResourceInterface, SiteContextedInterface, TimestampableInterface, \Stringable
{
    public function getId(): ?int;

    public function getCode(): ?string;

    public function setCode(string $code): void;
}
