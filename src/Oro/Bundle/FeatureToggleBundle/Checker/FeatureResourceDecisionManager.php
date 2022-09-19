<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Makes decisions whether a feature related resource is enabled or not.
 */
class FeatureResourceDecisionManager implements FeatureResourceDecisionManagerInterface
{
    private FeatureDecisionManagerInterface $featureDecisionManager;
    private ConfigurationManager $configManager;

    public function __construct(
        FeatureDecisionManagerInterface $featureDecisionManager,
        ConfigurationManager $configManager
    ) {
        $this->featureDecisionManager = $featureDecisionManager;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function decide(string $resource, string $resourceType, object|int|null $scopeIdentifier): bool
    {
        $features = $this->configManager->getFeaturesByResource($resourceType, $resource);
        foreach ($features as $feature) {
            if (!$this->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }

    private function isFeatureEnabled(string $feature, object|int|null $scopeIdentifier = null): bool
    {
        return $this->featureDecisionManager->decide($feature, $scopeIdentifier);
    }
}
