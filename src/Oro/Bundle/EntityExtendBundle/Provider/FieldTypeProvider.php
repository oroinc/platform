<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

/**
 * Provides available fields and it's config data.
 */
class FieldTypeProvider
{
    protected ConfigManager $configManager;

    protected array $supportedFields = [];
    protected array $supportedRelations = [];

    public function __construct(ConfigManager $configManager, array $fields = [], array $relations = [])
    {
        $this->configManager = $configManager;
        $this->supportedFields = $fields;
        $this->supportedRelations = $relations;
    }

    public function addSupportedFieldType(string $fieldType): void
    {
        if (!\in_array($fieldType, $this->supportedFields, true)) {
            $this->supportedFields[] = $fieldType;
        }
    }

    public function addSupportedRelation(string $relation): void
    {
        if (!\in_array($relation, $this->supportedRelations, true)) {
            $this->supportedRelations[] = $relation;
        }
    }

    public function getSupportedFieldTypes(): array
    {
        return $this->supportedFields;
    }

    public function getSupportedRelationTypes(): array
    {
        return $this->supportedRelations;
    }

    /**
     * @param string $fieldType
     * @param string $configType
     *
     * @return array [scope => [parameter_name => [parameter_config_item, ...], ...], ...]
     */
    public function getFieldProperties($fieldType, $configType = PropertyConfigContainer::TYPE_FIELD): array
    {
        $properties = [];

        foreach ($this->configManager->getProviders() as $provider) {
            $propertyConfig = $provider->getPropertyConfig();
            if ($propertyConfig->hasForm($configType, $fieldType)) {
                $scope = $provider->getScope();
                foreach ($propertyConfig->getItems($configType) as $code => $item) {
                    if (isset($item['import_export']['import_template']['use_in_template'])
                        && true === $item['import_export']['import_template']['use_in_template']
                        && !isset($properties[$scope][$code])
                        && (
                            !isset($item['options']['allowed_type'])
                            || in_array($fieldType, $item['options']['allowed_type'], true)
                        )
                    ) {
                        $properties[$scope][$code] = $item;
                    }
                }
            }
        }

        return $properties;
    }
}
