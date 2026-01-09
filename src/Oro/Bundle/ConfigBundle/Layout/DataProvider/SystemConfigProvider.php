<?php

namespace Oro\Bundle\ConfigBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides system configuration values for use in layout templates and data providers.
 *
 * Acts as a wrapper around the {@see ConfigManager} to expose configuration values in a layout
 * context. This provider allows layout templates and layout data providers to access system
 * configuration settings with support for scope-specific values, default values, and full
 * configuration details. It simplifies access to configuration data within the layout system
 * by delegating to the underlying ConfigManager.
 */
class SystemConfigProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

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
