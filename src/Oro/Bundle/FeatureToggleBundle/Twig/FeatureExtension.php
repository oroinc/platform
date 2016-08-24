<?php

namespace Oro\Bundle\FeatureToggleBundle\Twig;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;

class FeatureExtension extends \Twig_Extension
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
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            'feature_enabled' => new \Twig_Function_Method($this, 'isFeatureEnabled'),
            'feature_resource_enabled' => new \Twig_Function_Method($this, 'isResourceEnabled'),
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'oro_feature_extension';
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
