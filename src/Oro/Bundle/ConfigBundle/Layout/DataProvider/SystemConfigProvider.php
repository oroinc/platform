<?php

namespace Oro\Bundle\ConfigBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

class SystemConfigProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string          $name
     * @param bool            $default
     * @param bool            $full
     * @param null|int|object $scopeIdentifier
     *
     * @return mixed
     */
    public function getValue($name, $default = false, $full = false, $scopeIdentifier = null)
    {
        return $this->configManager->get($name, $default, $full, $scopeIdentifier);
    }
}
