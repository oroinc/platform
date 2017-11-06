<?php

namespace Oro\Bundle\ApiBundle\Tests\Functional\Environment;

use Symfony\Component\Config\Definition\Processor;

use Oro\Bundle\ApiBundle\Config\ConfigExtensionRegistry;
use Oro\Bundle\ApiBundle\Config\Definition\ApiConfiguration;
use Oro\Bundle\ApiBundle\Provider\ConfigBag;

class TestConfigBag extends ConfigBag
{
    /** @var ConfigExtensionRegistry */
    private $extensionRegistry;

    /** @var array */
    private $originalConfig;

    /** @var array */
    private $appendedConfig = [];

    /** @var bool */
    private $hasChanges = false;

    /**
     * @param ConfigExtensionRegistry $extensionRegistry
     */
    public function setExtensionRegistry(ConfigExtensionRegistry $extensionRegistry)
    {
        $this->extensionRegistry = $extensionRegistry;
        if (null === $this->originalConfig) {
            $this->originalConfig = $this->config;
        }
    }

    /**
     * @param string $entityClass
     * @param array  $config
     */
    public function appendEntityConfig($entityClass, array $config)
    {
        $this->appendedConfig['entities'][$entityClass] = $config;
        $processor = new Processor();
        $this->config = $processor->processConfiguration(
            new ApiConfiguration($this->extensionRegistry),
            [$this->originalConfig, $this->appendedConfig]
        );
        $this->hasChanges = true;
    }

    /**
     * @return bool
     */
    public function restoreConfigs()
    {
        if (!$this->hasChanges) {
            return false;
        }

        $this->config = $this->originalConfig;
        $this->appendedConfig = [];
        $this->hasChanges = false;

        return true;
    }
}
