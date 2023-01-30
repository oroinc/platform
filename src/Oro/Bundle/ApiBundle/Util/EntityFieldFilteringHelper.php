<?php

namespace Oro\Bundle\ApiBundle\Util;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

/**
 * Provides a set of methods to help filtering entity fields that should be exposed via API.
 */
class EntityFieldFilteringHelper
{
    private ConfigManager $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string      $entityClass
     * @param string[]    $fieldNames
     * @param string[]    $explicitlyConfiguredFieldNames
     * @param string|null $exclusionPolicy
     *
     * @return string[]
     */
    public function filterEntityFields(
        string $entityClass,
        array $fieldNames,
        array $explicitlyConfiguredFieldNames,
        ?string $exclusionPolicy
    ): array {
        if (ConfigUtil::EXCLUSION_POLICY_ALL === $exclusionPolicy) {
            $filteredWysiwygFields = [];
            foreach ($fieldNames as $fieldName) {
                if (\in_array($fieldName, $explicitlyConfiguredFieldNames, true)) {
                    $filteredWysiwygFields[] = $fieldName;
                }
            }

            return $filteredWysiwygFields;
        }
        if (ConfigUtil::EXCLUSION_POLICY_CUSTOM_FIELDS === $exclusionPolicy
            && $this->isExtendSystemEntity($entityClass)
        ) {
            $filteredWysiwygFields = [];
            foreach ($fieldNames as $fieldName) {
                if (\in_array($fieldName, $explicitlyConfiguredFieldNames, true)
                    || !$this->isCustomField($entityClass, $fieldName)
                ) {
                    $filteredWysiwygFields[] = $fieldName;
                }
            }

            return $filteredWysiwygFields;
        }

        return $fieldNames;
    }

    public function isExtendSystemEntity(string $entityClass): bool
    {
        if (!$this->configManager->hasConfig($entityClass)) {
            return false;
        }

        $entityConfig = $this->configManager->getEntityConfig('extend', $entityClass);

        return
            $entityConfig->is('is_extend')
            && !$entityConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    public function isCustomField(string $entityClass, string $fieldName): bool
    {
        if (!$this->configManager->hasConfig($entityClass, $fieldName)) {
            return false;
        }

        $fieldConfig = $this->configManager->getFieldConfig('extend', $entityClass, $fieldName);

        return
            $fieldConfig->is('is_extend')
            && $fieldConfig->is('owner', ExtendScope::OWNER_CUSTOM);
    }

    public function isCustomAssociation(string $entityClass, string $associationName): bool
    {
        return
            $this->isCustomField($entityClass, $associationName)
            || (
                str_starts_with($associationName, ExtendConfigDumper::DEFAULT_PREFIX)
                && $this->isCustomField(
                    $entityClass,
                    substr($associationName, \strlen(ExtendConfigDumper::DEFAULT_PREFIX))
                )
            );
    }
}
