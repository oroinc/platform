<?php

namespace Oro\Bundle\LocaleBundle\Model;

use Oro\Bundle\LocaleBundle\Entity\FallbackTrait;

/**
 * Provides fallback functionality for extended entities.
 *
 * This class uses the FallbackTrait to enable fallback behavior for entities that need
 * to support localization with fallback to parent localizations or system defaults.
 */
class ExtendFallback
{
    use FallbackTrait;
}
