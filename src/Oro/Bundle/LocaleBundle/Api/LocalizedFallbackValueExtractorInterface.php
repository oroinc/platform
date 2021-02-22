<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * Represents a service to extract API suitable value from a specific localized fallback value.
 */
interface LocalizedFallbackValueExtractorInterface
{
    /**
     * @param AbstractLocalizedFallbackValue $value
     *
     * @return string|null
     */
    public function extractValue(AbstractLocalizedFallbackValue $value): ?string;
}
