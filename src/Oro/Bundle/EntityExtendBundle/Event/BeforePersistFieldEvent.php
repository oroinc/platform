<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class BeforePersistFieldEvent extends Event
{
    const EVENT_NAME = "oro_entity_extend.before_persist_field";

    /**
     * @var ConfigInterface
     */
    protected $entityConfig;

    /**
     * @var ConfigInterface
     */
    protected $originalExtendEntityConfig;

    /**
     * @var FieldConfigModel
     */
    protected $fieldConfigModel;

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param ConfigInterface  $entityConfig
     */
    public function __construct(
        FieldConfigModel $fieldConfigModel,
        ConfigInterface $entityConfig,
        ConfigInterface $originalExtendEntityConfig
    ) {
        $this->entityConfig = $entityConfig;
        $this->fieldConfigModel = $fieldConfigModel;
        $this->originalExtendEntityConfig = $originalExtendEntityConfig;
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

    /**
     * @return ConfigInterface
     */
    public function getOriginalExtendEntityConfig()
    {
        return $this->originalExtendEntityConfig;
    }
}
