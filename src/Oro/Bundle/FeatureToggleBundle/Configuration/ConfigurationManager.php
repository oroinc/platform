<?php

namespace Oro\Bundle\FeatureToggleBundle\Configuration;

class ConfigurationManager
{
    /**
     * @var ConfigurationProvider
     */
    protected $configurationProvider;

    /**
     * @param ConfigurationProvider $configurationProvider
     */
    public function __construct(ConfigurationProvider $configurationProvider)
    {
        $this->configurationProvider = $configurationProvider;
    }

    /**
     * @param string $feature
     * @param string $node
     * @param null|mixed $default
     * @return mixed
     */
    public function get($feature, $node, $default = null)
    {
        $configuration = $this->configurationProvider->getConfiguration();
        if (array_key_exists($feature, $configuration) && array_key_exists($node, $configuration[$feature])) {
            return $configuration[$feature][$node];
        }

        return $default;
    }
}
