<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class EntityConfigStructureOptionsListener
{
    const OPTION_NAME = 'configurable';

    /** @var ConfigProvider */
    protected $configProvider;

    /**
     * @param ConfigProvider $configProvider
     */
    public function __construct(ConfigProvider $configProvider)
    {
        $this->configProvider = $configProvider;
    }

    /**
     * @param EntityStructureOptionsEvent $event
     */
    public function onOptionsRequest(EntityStructureOptionsEvent $event)
    {
        $data = $event->getData();
        foreach ($data as $entityStructure) {
            $className = $entityStructure->getClassName();
            $fields = $entityStructure->getFields();
            $isEntityConfigurable = $this->configProvider->hasConfig($className);
            $entityStructure->addOption(self::OPTION_NAME, $isEntityConfigurable);
            foreach ($fields as $field) {
                $isFieldConfigurable = $this->configProvider->hasConfig($className, $field->getName());
                $field->addOption(self::OPTION_NAME, $isFieldConfigurable);
            }
        }
        $event->setData($data);
    }
}
