<?php

namespace Oro\Bundle\ConfigBundle\Event;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use Symfony\Component\EventDispatcher\Event;

class LoadConfigEvent extends Event
{
    const NAME = 'oro_config.load_config';

    /** @var ConfigManager */
    protected $configManager;

    /** @var string */
    protected $key;

    /** @var array */
    protected $value;

    /** @var bool */
    protected $full;

    /**
     * @param ConfigManager $configManager
     * @param string $key
     * @param array $value
     * @param bool $full
     */
    public function __construct(ConfigManager $configManager, $key, $value, $full)
    {
        $this->configManager = $configManager;
        $this->key = $key;
        $this->value = $value;
        $this->full = $full;
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
     * @return array
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param array $value
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
}
