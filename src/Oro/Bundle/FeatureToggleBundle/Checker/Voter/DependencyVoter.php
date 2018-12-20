<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker\Voter;

use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Check feature dependencies.
 */
class DependencyVoter implements VoterInterface
{
    /**
     * @var FeatureChecker
     */
    private $featureChecker;

    /**
     * @var ConfigurationManager
     */
    private $featureConfigManager;

    /**
     * @param FeatureChecker $featureChecker
     * @param ConfigurationManager $featureConfigManager
     */
    public function __construct(FeatureChecker $featureChecker, ConfigurationManager $featureConfigManager)
    {
        $this->featureChecker = $featureChecker;
        $this->featureConfigManager = $featureConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        $dependOnFeatures = $this->featureConfigManager->getFeatureDependencies($feature);
        if (!count($dependOnFeatures)) {
            return self::FEATURE_ABSTAIN;
        }

        foreach ($dependOnFeatures as $dependOnFeature) {
            if (!$this->featureChecker->isFeatureEnabled($dependOnFeature, $scopeIdentifier)) {
                return self::FEATURE_DISABLED;
            }
        }

        return self::FEATURE_ENABLED;
    }
}
