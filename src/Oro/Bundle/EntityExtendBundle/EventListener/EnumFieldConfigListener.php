<?php

namespace Oro\Bundle\EntityExtendBundle\EventListener;

use Oro\Bundle\EntityConfigBundle\Config\ConfigHelper;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\FieldConfigEvent;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Update serialized enum field config.
 */
class EnumFieldConfigListener
{
    public function __construct(private ConfigHelper $configHelper)
    {
    }

    public function createFieldConfig(FieldConfigEvent $event): void
    {
        $configManager = $event->getConfigManager();
        $fieldConfig = $configManager->getConfigFieldModel(
            $event->getClassName(),
            $event->getFieldName()
        );
        if (!ExtendHelper::isEnumerableType($fieldConfig->getType())) {
            return;
        }
        $this->updateFieldConfig($fieldConfig);
    }

    private function updateFieldConfig(FieldConfigModel $extendFieldConfig): void
    {
        $this->configHelper->updateFieldConfigs(
            $extendFieldConfig,
            [
                'extend' => [
                    'state' => ExtendScope::STATE_ACTIVE,
                    'is_serialized' => true
                ],
            ]
        );
    }
}
