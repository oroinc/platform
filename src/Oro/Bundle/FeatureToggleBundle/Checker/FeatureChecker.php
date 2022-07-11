<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Checks a state of a feature and it's parts.
 */
class FeatureChecker
{
    private FeatureDecisionManagerInterface $featureDecisionManager;
    private ConfigurationManager $configManager;

    public function __construct(
        ConfigurationManager $configManager,
        FeatureDecisionManagerInterface $featureDecisionManager
    ) {
        $this->configManager = $configManager;
        $this->featureDecisionManager = $featureDecisionManager;
    }

    public function isFeatureEnabled(string $feature, object|int|null $scopeIdentifier = null): bool
    {
        return $this->featureDecisionManager->decide($feature, $scopeIdentifier);
    }

    public function isResourceEnabled(
        string $resource,
        string $resourceType,
        object|int|null $scopeIdentifier = null
    ): bool {
        $features = $this->configManager->getFeaturesByResource($resourceType, $resource);
        foreach ($features as $feature) {
            if (!$this->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }

    public function getDisabledResourcesByType(string $resourceType): array
    {
        $disabledResources = [];
        $resources = $this->configManager->getResourcesByType($resourceType);
        foreach ($resources as $resource => $features) {
            $isResourceEnabled = false;
            foreach ($features as $feature) {
                if ($this->isFeatureEnabled($feature)) {
                    $isResourceEnabled = true;
                    break;
                }
            }
            if (!$isResourceEnabled) {
                $disabledResources[] = $resource;
            }
        }

        return $disabledResources;
    }

    public function resetCache(): void
    {
        $this->featureDecisionManager->reset();
    }
}
