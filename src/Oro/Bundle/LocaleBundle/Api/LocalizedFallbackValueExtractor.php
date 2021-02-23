<?php

namespace Oro\Bundle\LocaleBundle\Api;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;

/**
 * The default implementation of a service to extract API suitable value from a specific localized fallback value.
 */
class LocalizedFallbackValueExtractor implements LocalizedFallbackValueExtractorInterface
{
    /**
     * {@inheritDoc}
     */
    public function extractValue(AbstractLocalizedFallbackValue $value): ?string
    {
        return $value->getString() ?? $value->getText();
    }
}
