<?php

declare(strict_types=1);

namespace Gingerminds\CmsBundle\Block;

use Gingerminds\CmsBundle\GingermindsCmsBundle;

/**
 * Base of a block: label `block.<key>.label` of the GingermindsCms domain, picker weight 100.
 * A project block translated in its own domain overrides getTranslationDomain().
 */
abstract class AbstractBlock implements BlockInterface
{
    public function getLabel(): string
    {
        return 'block.' . static::getKey() . '.label';
    }

    public function getOrder(): int
    {
        return 100;
    }

    public function getTranslationDomain(): string
    {
        return GingermindsCmsBundle::TRANSLATION_DOMAIN;
    }
}
