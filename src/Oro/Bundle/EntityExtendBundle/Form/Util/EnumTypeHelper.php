<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides utility methods for building forms that contain enums and multi-enums fields.
 */
class EnumTypeHelper extends ConfigTypeHelper
{
    const MULTI_ENUM = 'multiEnum';
    const TYPE_ENUM = 'enum';

    /**
     * Checks if the given entity/field has an enum code
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    public function hasEnumCode($className, $fieldName = null)
    {
        $enumCode = $this->getEnumCode($className, $fieldName);

        return !empty($enumCode);
    }

    /**
     * Returns an enum code for the given entity/field
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return string|null
     */
    public function getEnumCode($className, $fieldName = null)
    {
        $enumConfigProvider = $this->configManager->getProvider('enum');
        if ($enumConfigProvider->hasConfig($className, $fieldName)) {
            return $enumConfigProvider->getConfig($className, $fieldName)
                ->get($fieldName ? 'enum_code' : 'code');
        }

        return null;
    }

    /**
     * Checks if there are any other fields except the given field which use the given enum.
     *
     * @param string $enumCode
     * @param string $className
     * @param string $fieldName
     *
     * @return bool
     */
    public function hasOtherReferences($enumCode, $className, $fieldName)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $entityConfigs        = $extendConfigProvider->getConfigs();
        foreach ($entityConfigs as $entityConfig) {
            if (!$entityConfig->is('is_extend')) {
                continue;
            }

            $enumFieldConfigs = $enumConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($enumFieldConfigs as $enumFieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $enumFieldConfig->getId();
                if (!in_array($fieldConfigId->getFieldType(), [self::TYPE_ENUM, self::MULTI_ENUM])) {
                    // skip not enum fields
                    continue;
                }
                if ($fieldConfigId->getFieldName() === $fieldName
                    && $fieldConfigId->getClassName() === $className
                ) {
                    // skip current field
                    continue;
                }
                $fieldEnumCode = $enumFieldConfig->get('enum_code');
                if (!empty($fieldEnumCode) && $fieldEnumCode === $enumCode) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Checks if the given entity/field is system (extend.owner = ExtendScope::OWNER_SYSTEM)
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    public function isSystem($className, $fieldName = null)
    {
        $extendConfigProvider = $this->configManager->getProvider('extend');
        if ($extendConfigProvider->hasConfig($className, $fieldName)) {
            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);
            if ($extendConfig->is('owner', ExtendScope::OWNER_SYSTEM)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the list of data type keys for all public enums
     *
     * @return string[] key = enum code, value = data type key
     */
    public function getPublicEnumTypes()
    {
        $result = [];

        $enumConfigProvider   = $this->configManager->getProvider('enum');
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $entityConfigs        = $extendConfigProvider->getConfigs(null, true);
        foreach ($entityConfigs as $entityConfig) {
            if (!ExtendHelper::isEnumValueEntityAccessible($entityConfig)) {
                continue;
            }

            $className  = $entityConfig->getId()->getClassName();
            $enumConfig = $enumConfigProvider->getConfig($className);
            if (!$enumConfig->is('public')) {
                continue;
            }

            $enumCode          = $enumConfig->get('code');
            $result[$enumCode] = ($enumConfig->is('multiple') ? self::MULTI_ENUM : self::TYPE_ENUM) . '||' . $enumCode;
        }

        return $result;
    }
}
