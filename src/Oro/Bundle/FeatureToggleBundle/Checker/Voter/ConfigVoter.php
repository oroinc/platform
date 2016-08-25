<?php

namespace Oro\Bundle\FeatureToggleBundle\Checker\Voter;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Configuration\ConfigurationManager;

class ConfigVoter implements VoterInterface
{
    const TOGGLE_KEY = 'toggle';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ConfigurationManager $featureConfigManager
     */
    protected $featureConfigManager;

    /**
     * @param ConfigManager $configManager
     * @param ConfigurationManager $featureConfigManager
     */
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

        if ($this->configManager->get($toggle, false, false, $scopeIdentifier)) {
            return self::FEATURE_ENABLED;
        } else {
            return self::FEATURE_DISABLED;
        }
    }
}
