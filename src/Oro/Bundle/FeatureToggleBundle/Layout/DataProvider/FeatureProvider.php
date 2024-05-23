<?php

namespace Oro\Bundle\FeatureToggleBundle\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Provides:
 *  - feature state
 *  - feature state by resource
 */
class FeatureProvider
{
    public function __construct(protected FeatureChecker $featureChecker)
    {
    }

    public function isFeatureEnabled($feature, object|int|null $scopeIdentifier = null): bool
    {
        return $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier);
    }

    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        return $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
