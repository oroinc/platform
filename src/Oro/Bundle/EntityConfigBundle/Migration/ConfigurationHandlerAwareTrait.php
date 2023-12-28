<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;

/**
 * This trait can be used by migrations that implement {@see ConfigurationHandlerAwareInterface}.
 */
trait ConfigurationHandlerAwareTrait
{
    private ConfigurationHandler $configurationHandler;

    public function setConfigurationHandler(ConfigurationHandler $configurationHandler): void
    {
        $this->configurationHandler = $configurationHandler;
    }
}
