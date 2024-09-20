<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityClassProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides class names for enum option entities.
 */
class EnumOptionEntityClassProvider implements EntityClassProviderInterface
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getClassNames(): array
    {
        $result = [];
        $entityConfigIds = $this->configManager->getIds('extend', null, true);
        foreach ($entityConfigIds as $entityConfigId) {
            $entityClass = $entityConfigId->getClassName();
            /** @var FieldConfigId[] $fieldConfigIds */
            $fieldConfigIds = $this->configManager->getIds('extend', $entityClass, true);
            foreach ($fieldConfigIds as $fieldConfigId) {
                if (!ExtendHelper::isEnumerableType($fieldConfigId->getFieldType())) {
                    continue;
                }
                $fieldName = $fieldConfigId->getFieldName();
                if (!ExtendHelper::isFieldAccessible(
                    $this->configManager->getFieldConfig('extend', $entityClass, $fieldName)
                )) {
                    continue;
                }
                $enumCode = $this->configManager->getFieldConfig('enum', $entityClass, $fieldName)->get('enum_code');
                if (!$enumCode) {
                    continue;
                }
                $result[] = ExtendHelper::getOutdatedEnumOptionClassName($enumCode);
            }
        }
        $result[] = EnumOption::class;

        return $result;
    }
}
