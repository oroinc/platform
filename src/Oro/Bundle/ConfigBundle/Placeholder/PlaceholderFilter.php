<?php

namespace Oro\Bundle\ConfigBundle\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

/**
 * Provides methods that can be used in placeholders to determine whether configuration options are turned on
 * for the current logged in user.
 */
class PlaceholderFilter
{
    /** @var ConfigManager */
    private $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Checks whether the given configuration option is turned on for the current logged in user.
     *
     * @param string $name The name of a config option in "{bundle}.{setting}" format
     *
     * @return bool
     */
    public function isEnabled(string $name): bool
    {
        return (bool)$this->configManager->get($name);
    }
}
