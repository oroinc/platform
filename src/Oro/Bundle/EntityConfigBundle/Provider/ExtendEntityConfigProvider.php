<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Class ExtendEntityConfigProvider corresponds for returning configs for extend entities
 * (or only entities with attributes if needed)
 */
class ExtendEntityConfigProvider implements ExtendEntityConfigProviderInterface
{
    /** @var ConfigManager */
    private $configManager;

    /** @var bool */
    private $attributesOnly = false;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function enableAttributesOnly()
    {
        $this->attributesOnly = true;
    }

    /**
     * {@inheritdoc}
     */
    public function getExtendEntityConfigs($filter = null)
    {
        $configsToReturn = [];
        $attributeProvider = $this->configManager->getProvider('attribute');
        $extendProvider = $this->configManager->getProvider('extend');
        $enumConfigProvider = $this->configManager->getProvider('enum');

        $extendConfigs = $filter ?
            $extendProvider->filter($filter, null, true) :
            $extendProvider->getConfigs(null, true);

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->is('is_extend')) {
                $className = $extendConfig->getId()->getClassName();
                if ($this->attributesOnly && !$attributeProvider->getConfig($className)->is('has_attributes')) {
                    continue;
                }
                $configsToReturn[] = $extendConfig;

                if ($this->attributesOnly) {
                    $fieldConfigs = $extendProvider->getConfigs($extendConfig->getId()->getClassName());
                    $configsToReturn = $this->getExtendConfigForEnum(
                        $fieldConfigs,
                        $enumConfigProvider,
                        $extendProvider,
                        $configsToReturn
                    );
                }
            }
        }

        return $configsToReturn;
    }

    /**
     * @param ConfigInterface[] $fieldConfigs
     * @param ConfigProvider $enumConfigProvider
     * @param ConfigProvider $extendProvider
     * @param array $configsToReturn
     * @return array
     */
    private function getExtendConfigForEnum(
        array $fieldConfigs,
        $enumConfigProvider,
        $extendProvider,
        array $configsToReturn
    ) {
        foreach ($fieldConfigs as $fieldConfig) {
            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $fieldConfig->getId();
            $fieldType = $fieldConfigId->getFieldType();
            if (!in_array($fieldType, ['enum', 'multiEnum'], true)) {
                continue;
            }

            $enumFieldConfig = $enumConfigProvider->getConfig(
                $fieldConfigId->getClassName(),
                $fieldConfigId->getFieldName()
            );
            $enumCode = $enumFieldConfig->get('enum_code');

            if (!$enumCode) {
                continue;
            }
            $enumValueClassName = ExtendHelper::buildEnumValueClassName($enumCode);
            $configsToReturn[$enumValueClassName] = $extendProvider->getConfig($enumValueClassName);
        }

        return $configsToReturn;
    }
}
