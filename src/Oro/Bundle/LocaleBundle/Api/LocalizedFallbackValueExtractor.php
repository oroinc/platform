<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;

/**
 * The default implementation of a service to extract API suitable value from a specific localized fallback value.
 */
class LocalizedFallbackValueExtractor implements LocalizedFallbackValueExtractorInterface
{
    /**
     * {@inheritDoc}
     */
    public function extractValue(LocalizedFallbackValue $value): ?string
    {
        return $value->getString() ?? $value->getText();
    }
}
