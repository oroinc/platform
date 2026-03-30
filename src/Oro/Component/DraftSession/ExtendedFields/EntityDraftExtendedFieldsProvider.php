<?php

declare(strict_types=1);

namespace Oro\Component\DraftSession\ExtendedFields;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager as EntityConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Provides a list of applicable custom extended fields for a given entity class,
 * filtering by form visibility, accessibility, and target entity constraints.
 */
class EntityDraftExtendedFieldsProvider
{
    /** @var array<string, list<string>> Map of className => list of excluded field names */
    private array $excludedFields = [];

    public function __construct(
        private readonly EntityConfigManager $configManager,
    ) {
    }

    public function addExcludedField(string $className, string $fieldName): void
    {
        $this->excludedFields[$className][] = $fieldName;
    }

    /**
     * Returns applicable custom extended fields that are enabled on the form.
     *
     * @param string $className
     *
     * @return array<string, string> Map of fieldName => fieldType
     */
    public function getApplicableExtendedFields(string $className): array
    {
        $fields = [];
        $extendConfigProvider = $this->configManager->getProvider('extend');
        $formConfigProvider = $this->configManager->getProvider('form');
        $attributeConfigProvider = $this->configManager->getProvider('attribute');
        $excludedFieldNames = $this->excludedFields[$className] ?? [];

        foreach ($formConfigProvider->getConfigs($className) as $formConfig) {
            if (!$formConfig->is('is_enabled')) {
                continue;
            }

            /** @var FieldConfigId $fieldConfigId */
            $fieldConfigId = $formConfig->getId();
            $fieldName = $fieldConfigId->getFieldName();

            if ($this->shouldSkipField($className, $fieldName, $excludedFieldNames, $attributeConfigProvider)) {
                continue;
            }

            $extendConfig = $extendConfigProvider->getConfig($className, $fieldName);

            if ($this->shouldSkipExtendConfig($extendConfig, $extendConfigProvider)) {
                continue;
            }

            $fields[$fieldName] = $extendConfig->getId()->getFieldType();
        }

        return $fields;
    }

    private function shouldSkipField(
        string $className,
        string $fieldName,
        array $excludedFieldNames,
        $attributeConfigProvider
    ): bool {
        if (in_array($fieldName, $excludedFieldNames, true)) {
            return true;
        }

        // Skip attributes - they are handled by a different mechanism
        if (
            $attributeConfigProvider->hasConfig($className, $fieldName)
            && $attributeConfigProvider->getConfig($className, $fieldName)->is('is_attribute')
        ) {
            return true;
        }

        return false;
    }

    private function shouldSkipExtendConfig($extendConfig, $extendConfigProvider): bool
    {
        // Only process custom extended fields
        if (!$extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)) {
            return true;
        }

        if (!ExtendHelper::isFieldAccessible($extendConfig)) {
            return true;
        }

        $fieldType = $extendConfig->getId()->getFieldType();

        // Skip polymorphic ("to-any") relation types
        if (in_array($fieldType, RelationType::$toAnyRelations, true)) {
            return true;
        }

        // Skip if target entity is not accessible or is hidden
        if ($extendConfig->has('target_entity')) {
            $targetEntityClass = $extendConfig->get('target_entity');
            if ($this->configManager->isHiddenModel($targetEntityClass)) {
                return true;
            }

            if (!ExtendHelper::isEntityAccessible($extendConfigProvider->getConfig($targetEntityClass))) {
                return true;
            }
        }

        return false;
    }
}
