<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class BeforePersistFieldEvent extends Event
{
    const EVENT_NAME = "oro_entity_extend.before_persist_field";

    /**
     * @var ConfigInterface
     */
    protected $entityConfig;

    /**
     * @var FieldConfigModel
     */
    protected $fieldConfigModel;

    /**
     * @param ConfigInterface $config
     */
    public function __construct(FieldConfigModel $fieldConfigModel, ConfigInterface $entityConfig)
    {
        $this->entityConfig = $entityConfig;
        $this->fieldConfigModel = $fieldConfigModel;
    }

    /**
     * @return ConfigInterface
     */
    public function getEntityConfig()
    {
        return $this->entityConfig;
    }

    /**
     * @return FieldConfigModel
     */
    public function getFieldConfigModel()
    {
        return $this->fieldConfigModel;
    }
}
