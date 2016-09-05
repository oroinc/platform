<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Symfony\Component\EventDispatcher\Event;

class ConfigGetEvent extends Event
{
    const NAME = 'oro_config.get';

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $key;

    /** @var array|string */
    protected $value;

    /** @var bool */
    protected $full;

    /**
     * @var int
     */
    protected $scopeId;

    /**
     * @param ConfigManager $configManager
     * @param string $key
     * @param array|string $value
     * @param bool $full
     * @param int $scopeId
     */
    public function __construct(ConfigManager $configManager, $key, $value, $full, $scopeId)
    {
        $this->configManager = $configManager;
        $this->key = $key;
        $this->value = $value;
        $this->full = $full;
        $this->scopeId = $scopeId;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * @return string
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * @return array|string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array|string $value
     */
    public function setValue($value)
    {
        $this->value = $value;
    }

    /**
     * @return bool
     */
    public function isFull()
    {
        return $this->full;
    }

    /**
     * @return int
     */
    public function getScopeId()
    {
        return $this->scopeId;
    }
}
