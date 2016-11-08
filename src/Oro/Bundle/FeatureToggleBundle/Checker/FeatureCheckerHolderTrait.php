<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker;

trait FeatureCheckerHolderTrait
{
    /**
     * @var FeatureChecker
     */
    protected $featureChecker;

    /**
     * @var array
     */
    protected $features = [];

    /**
     * @param FeatureChecker $checker
     */
    public function setFeatureChecker(FeatureChecker $checker)
    {
        $this->featureChecker = $checker;
    }

    /**
     * @param string $feature
     */
    public function addFeature($feature)
    {
        $this->features[] = $feature;
    }

    /**
     * @param null|int|object $scopeIdentifier
     * @return bool
     */
    public function isFeaturesEnabled($scopeIdentifier = null)
    {
        foreach ($this->features as $feature) {
            if (!$this->featureChecker->isFeatureEnabled($feature, $scopeIdentifier)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $resource
     * @param string $resourceType
     * @param object|int|null $scopeIdentifier
     * @return bool
     */
    public function isResourceEnabled($resource, $resourceType, $scopeIdentifier = null)
    {
        return $this->featureChecker->isResourceEnabled($resource, $resourceType, $scopeIdentifier);
    }

    /**
     * @param string|null $route
     * @param null|int|object $scopeIdentifier
     *
     * @return bool
     */
    public function isRouteEnabled($route = null, $scopeIdentifier = null)
    {
        return $this->featureChecker->isResourceEnabled($route, 'routes', $scopeIdentifier);
    }
}
