<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
