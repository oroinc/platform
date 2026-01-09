<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Dispatched when field configuration is modified.
 *
 * This event is triggered during field configuration changes and provides access to the entity class name,
 * field name, and the configuration manager.
 * Listeners can use this event to react to field-level configuration updates.
 */
class FieldConfigEvent extends Event
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $fieldName;

    /**
     * @param string        $className     The FQCN of an entity
     * @param string        $fieldName     The name of a field
     * @param ConfigManager $configManager The entity config manager
     */
    public function __construct($className, $fieldName, ConfigManager $configManager)
    {
        $this->className     = $className;
        $this->fieldName     = $fieldName;
        $this->configManager = $configManager;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }

    /**
     * @return string
     */
    public function getFieldName()
    {
        return $this->fieldName;
    }
}
