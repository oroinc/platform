<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

class RenameFieldEvent extends Event
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
     * @var string
     */
    protected $newFieldName;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param string        $className Entity class name
     * @param string        $fieldName
     * @param string        $newFieldName
     * @param ConfigManager $configManager
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

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }
}
