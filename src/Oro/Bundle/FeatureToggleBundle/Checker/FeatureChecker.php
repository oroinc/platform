<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Checks a state of a feature and it's parts.
 */
class FeatureChecker
{
    private FeatureDecisionManagerInterface $featureDecisionManager;
    private FeatureResourceDecisionManagerInterface $featureResourceDecisionManager;
    private ConfigurationManager $configManager;

    public function __construct(
        FeatureDecisionManagerInterface $featureDecisionManager,
        FeatureResourceDecisionManagerInterface $featureResourceDecisionManager,
        ConfigurationManager $configManager
    ) {
        $this->featureResourceDecisionManager = $featureResourceDecisionManager;
        $this->featureDecisionManager = $featureDecisionManager;
        $this->configManager = $configManager;
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
        return $this->featureResourceDecisionManager->decide($resource, $resourceType, $scopeIdentifier);
    }

    public function getDisabledResourcesByType(string $resourceType): array
    {
        $disabledResources = [];
        $resources = $this->configManager->getResourcesByType($resourceType);
        foreach ($resources as $resource => $features) {
            if (!$this->isResourceEnabled($resource, $resourceType)) {
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
