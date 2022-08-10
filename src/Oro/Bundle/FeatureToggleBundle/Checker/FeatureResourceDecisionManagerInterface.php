<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

/**
 * Represents a service that is responsible to make decisions whether a feature related resource is enabled or not.
 */
interface FeatureResourceDecisionManagerInterface
{
    /**
     * Decides whether the feature related resource is enabled or not.
     */
    public function decide(string $resource, string $resourceType, object|int|null $scopeIdentifier): bool;
}
