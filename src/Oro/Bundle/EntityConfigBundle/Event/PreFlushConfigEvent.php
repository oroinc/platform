<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class PreFlushConfigEvent extends Event
{
    /** @var ConfigInterface[] */
    private $configs;

    /** @var ConfigManager */
    private $configManager;

    /** @var string */
    private $className;

    /** @var bool */
    private $fieldConfig;

    /**
     * @param ConfigInterface[] $configs
     * @param ConfigManager     $configManager
     */
    public function __construct($configs, ConfigManager $configManager)
    {
        $this->configs       = $configs;
        $this->configManager = $configManager;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @return ConfigInterface[]
     */
    public function getConfigs()
    {
        return $this->configs;
    }

    /**
     * @param string $scope
     *
     * @return ConfigInterface|null
     */
    public function getConfig($scope)
    {
        return isset($this->configs[$scope])
            ? $this->configs[$scope]
            : null;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        if (null === $this->className) {
            /** @var ConfigInterface $config */
            $config          = reset($this->configs);
            $this->className = $config->getId()->getClassName();
        }

        return $this->className;
    }

    /**
     * @return bool
     */
    public function isFieldConfig()
    {
        if (null === $this->fieldConfig) {
            /** @var ConfigInterface $config */
            $config            = reset($this->configs);
            $this->fieldConfig = $config->getId() instanceof FieldConfigId;
        }

        return $this->fieldConfig;
    }
}
