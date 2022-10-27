<?php

namespace Oro\Bundle\EntityConfigBundle\Migration;

use Oro\Bundle\EntityConfigBundle\EntityConfig\ConfigurationHandler;

/**
 * ConfigurationHandlerAwareInterface should be implemented by migration queries that use ConfigurationHandler to
 * processing(validation and set defaults) entity config and entity config field.
 */
interface ConfigurationHandlerAwareInterface
{
    /**
     * @param ConfigurationHandler $configurationHandler
     */
    public function setConfigurationHandler(ConfigurationHandler $configurationHandler): void;
}
