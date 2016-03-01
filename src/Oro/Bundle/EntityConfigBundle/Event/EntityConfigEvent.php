<?php

namespace Oro\Bundle\EntityConfigBundle\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

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
