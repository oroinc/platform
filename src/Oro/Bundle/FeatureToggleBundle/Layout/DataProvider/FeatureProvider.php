<?php

namespace Oro\Bundle\FeatureToggleBundle\Layout\DataProvider;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class FeatureProvider
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @param FeatureChecker $featureChecker
     */
    public function __construct(FeatureChecker $featureChecker)
    {
        $this->featureChecker = $featureChecker;
    }

    /**
     * @param string $feature
     * @param int|null $scopeIdentifier
     * @return bool
     */
    public function isFeatureEnabled($feature, $scopeIdentifier = null)
    {
        return $this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier);
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @param null $scopeIdentifier
     * @return bool
     */
    public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        return $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }
}
