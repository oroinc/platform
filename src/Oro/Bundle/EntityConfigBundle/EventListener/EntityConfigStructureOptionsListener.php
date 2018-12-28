<?php

namespace Oro\Bundle\EntityConfigBundle\EventListener;

use Oro\Bundle\EntityBundle\Event\EntityStructureOptionsEvent;
use Oro\Bundle\EntityBundle\Helper\UnidirectionalFieldHelper;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

/**
 * Adds "configurable" option for configurable fields (fields that have configuration in entity configs).
 */
class EntityConfigStructureOptionsListener
{
    private const OPTION_NAME = 'configurable';

    /** @var ConfigProvider */
    private $configProvider;

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
            foreach ($fields as $field) {
                $fieldName = $field->getName();
                if (UnidirectionalFieldHelper::isFieldUnidirectional($fieldName)) {
                    $realFieldName = UnidirectionalFieldHelper::getRealFieldName($fieldName);
                    $realFieldClass = UnidirectionalFieldHelper::getRealFieldClass($fieldName);
                } else {
                    $realFieldName = $fieldName;
                    $realFieldClass = $className;
                }
                if ($this->configProvider->hasConfig($realFieldClass, $realFieldName)) {
                    $field->addOption(self::OPTION_NAME, true);
                }
            }
        }
        $event->setData($data);
    }
}
