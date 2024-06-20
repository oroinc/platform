<?php

namespace Oro\Bundle\FeatureToggleBundle\Layout\DataProvider;

/**
 * This class was created to support the default_50 theme and can be removed when theme support is no longer needed
 *
 * Decorates: @FeatureProvider
 */
class FeatureProvider50SupportDecorator
{
    public function __construct(protected FeatureProvider $featureProvider)
    {
    }

    public function isFeatureEnabled($feature, object|int|string|null $scopeIdentifier = null): bool
    {
        if (is_string($scopeIdentifier)) {
            $scopeIdentifier = null;
        }

        return $this->featureProvider->isFeatureEnabled($feature, $scopeIdentifier);
    }

    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        return $this->featureProvider->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
