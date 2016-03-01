<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
