<?php

namespace Oro\Bundle\EntityBundle\ORM;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * An entity repository which need an access to the entity config subsystem must implement this interface.
 */
interface EntityConfigAwareRepositoryInterface
{
    /**
     * Sets the entity config manager.
     *
     * @param ConfigManager $entityConfigManager An instance of the entity config manager
     */
    public function setEntityConfigManager(ConfigManager $entityConfigManager);
}
