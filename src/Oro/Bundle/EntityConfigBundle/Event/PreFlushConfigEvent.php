<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class PreFlushConfigEvent extends Event
{
    /** @var ConfigInterface[] */
    private $configs;

    /** @var string */
    private $className;

    /** @var bool */
    private $fieldConfig;

    /**
     * @param ConfigInterface[] $configs       Entity or field configs to be flushed
     * @param ConfigManager     $configManager The entity config manager
     */
    public function __construct($configs, ConfigManager $configManager)
    {
        $this->configs       = $configs;
        $this->configManager = $configManager;
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
    public function isEntityConfig()
    {
        return !$this->isFieldConfig();
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
