<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class FieldConfigEvent extends Event
{
    /**
     * @var string
     */
    protected $className;

    /**
     * @var string
     */
    protected $fieldName;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param string        $className Entity class name
     * @param string        $fieldName
     * @param ConfigManager $configManager
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

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }
}
