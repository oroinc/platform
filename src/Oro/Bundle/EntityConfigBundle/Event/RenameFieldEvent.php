<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Dispatched when an entity field is renamed.
 *
 * This event is triggered when a field within an entity is renamed, providing access to the entity class name,
 * the old field name, the new field name, and the configuration manager. Listeners can use this event to update
 * related configurations, references, or perform cleanup operations associated with the field rename.
 */
class RenameFieldEvent extends Event
{
    /** @var string */
    protected $className;

    /** @var string */
    protected $fieldName;

    /** @var string */
    protected $newFieldName;

    /**
     * @param string        $className     The FQCN of an entity
     * @param string        $fieldName     The old name of a field
     * @param string        $newFieldName  The new name of a field
     * @param ConfigManager $configManager The entity config manager
     */
    public function __construct($className, $fieldName, $newFieldName, ConfigManager $configManager)
    {
        $this->className     = $className;
        $this->fieldName     = $fieldName;
        $this->newFieldName  = $newFieldName;
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

    /**
     * @return string
     */
    public function getNewFieldName()
    {
        return $this->newFieldName;
    }
}
