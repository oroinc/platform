<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Dispatched when entity configuration is modified.
 *
 * This event is triggered during entity configuration changes and provides access to the entity class name
 * and the configuration manager. Listeners can use this event to react to entity-level configuration updates.
 */
class EntityConfigEvent extends Event
{
    /** @var string */
    protected $className;

    /**
     * @param string        $className     The FQCN of an entity
     * @param ConfigManager $configManager The entity config manager
     */
    public function __construct($className, ConfigManager $configManager)
    {
        $this->className     = $className;
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
