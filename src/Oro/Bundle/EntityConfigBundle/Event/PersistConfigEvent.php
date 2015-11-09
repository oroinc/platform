<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event as SymfonyEvent;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * @deprecated since 1.9. Use PreFlushConfigEvent instead
 */
class PersistConfigEvent extends SymfonyEvent
{
    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigInterface $config, ConfigManager $configManager)
    {
        $this->config        = $config;
        $this->configManager = $configManager;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return ConfigIdInterface
     */
    public function getConfigId()
    {
        return $this->config->getId();
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }
}
