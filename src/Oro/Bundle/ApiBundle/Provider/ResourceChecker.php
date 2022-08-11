<?php

namespace Oro\Bundle\ApiBundle\Provider;

use Oro\Bundle\ApiBundle\Request\RequestType;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

/**
 * Default implementation of a service to check whether an entity is enabled for API.
 */
class ResourceChecker implements ResourceCheckerInterface
{
    private FeatureChecker $featureChecker;
    private ResourceCheckerConfigProvider $configProvider;
    private string $resourceType;

    public function __construct(
        FeatureChecker $featureChecker,
        ResourceCheckerConfigProvider $configProvider,
        string $resourceType
    ) {
        $this->featureChecker = $featureChecker;
        $this->configProvider = $configProvider;
        $this->resourceType = $resourceType;
    }

    /**
     * {@inheritDoc}
     */
    public function isResourceEnabled(
        string $entityClass,
        string $action,
        string $version,
        RequestType $requestType
    ): bool {
        if (!$this->featureChecker->isResourceEnabled($entityClass, $this->resourceType)) {
            return false;
        }

        $features = $this->configProvider->getApiResourceFeatures($entityClass, $action);
        foreach ($features as $feature) {
            if (!$this->featureChecker->isFeatureEnabled($feature)) {
                return false;
            }
        }

        return true;
    }
}
