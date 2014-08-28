<?php

namespace Oro\Bundle\EntityExtendBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Form\Util\ConfigTypeHelper;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

class EnumTypeHelper extends ConfigTypeHelper
{
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
     * Checks if there are any other fields except the given field which use the the given enum
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
            $enumFieldConfigs = $enumConfigProvider->getConfigs($entityConfig->getId()->getClassName());
            foreach ($enumFieldConfigs as $enumFieldConfig) {
                /** @var FieldConfigId $fieldConfigId */
                $fieldConfigId = $enumFieldConfig->getId();
                if (!in_array($fieldConfigId->getFieldType(), ['enum', 'multiEnum'])) {
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
}
