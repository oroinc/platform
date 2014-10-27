<?php

namespace Oro\Bundle\EntityExtendBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class BeforeDeletePersistFieldEvent extends Event
{
    const EVENT_NAME = "oro_entity_extend.before_delete_persist_field";

    /** @var ConfigInterface */
    protected $fieldConfig;

    /** @var ConfigInterface */
    protected $entityConfig;

    /** @var ConfigInterface */
    protected $originalExtendEntityConfig;

    /**
     * @param ConfigInterface $fieldConfig
     * @param ConfigInterface $entityConfig
     * @param ConfigInterface $originalExtendEntityConfig
     */
    public function __construct(
        ConfigInterface $fieldConfig,
        ConfigInterface $entityConfig,
        ConfigInterface $originalExtendEntityConfig
    ) {
        $this->fieldConfig = $fieldConfig;
        $this->entityConfig = $entityConfig;
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
     * @return ConfigInterface
     */
    public function getFieldConfig()
    {
        return $this->fieldConfig;
    }

    /**
     * @return ConfigInterface
     */
    public function getOriginalExtendEntityConfig()
    {
        return $this->originalExtendEntityConfig;
    }
}
