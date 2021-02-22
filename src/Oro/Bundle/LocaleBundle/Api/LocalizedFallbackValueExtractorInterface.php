<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * Represents a service to extract API suitable value from a specific localized fallback value.
 */
interface LocalizedFallbackValueExtractorInterface
{
    /**
     * @param LocalizedFallbackValue $value
     *
     * @return string|null
     */
    public function extractValue(LocalizedFallbackValue $value): ?string;
}
