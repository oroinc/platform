<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;

/**
 * This interface should be implemented by migration queries that depend on {@see ConfigurationHandler}.
 */
interface ConfigurationHandlerAwareInterface
{
    public function setConfigurationHandler(ConfigurationHandler $configurationHandler): void;
}
