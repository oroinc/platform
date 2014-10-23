<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class AfterFlushFieldEvent extends Event
{
    const EVENT_NAME = "oro_entity_extend.after_flush_field";

    /** @var string */
    protected $className;

    /** @var FieldConfigModel */
    protected $configModel;

    /**
     * @param string           $className
     * @param FieldConfigModel $configModel
     */
    public function __construct($className, FieldConfigModel $configModel)
    {
        $this->className   = $className;
        $this->configModel = $configModel;
    }

    /**
     * @return ConfigInterface
     */
    public function getConfigModel()
    {
        return $this->configModel;
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->className;
    }
}
