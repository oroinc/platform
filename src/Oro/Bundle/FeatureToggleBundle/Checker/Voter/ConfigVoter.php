<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

/**
 * Votes whether a feature ia available based on the state of configuration option associated with a feature.
 */
class ConfigVoter implements VoterInterface
{
    private const TOGGLE_KEY = 'toggle';

    private ConfigManager $configManager;
    private ConfigurationManager $featureConfigManager;

    public function __construct(ConfigManager $configManager, ConfigurationManager $featureConfigManager)
    {
        $this->configManager = $configManager;
        $this->featureConfigManager = $featureConfigManager;
    }

    /**
     * {@inheritdoc}
     */
    public function vote($feature, $scopeIdentifier = null)
    {
        $toggle = $this->featureConfigManager->get($feature, self::TOGGLE_KEY);
        if (!$toggle) {
            return self::FEATURE_ABSTAIN;
        }

        if ($this->configManager->get($toggle, false, false, $scopeIdentifier)) {
            return self::FEATURE_ENABLED;
        }

        return self::FEATURE_DISABLED;
    }
}
