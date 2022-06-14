<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

/**
 * The import template fixture that sets the example of data that can be used in import
 * of the custom fields or attributes.
 */
class EntityFieldFixture implements TemplateFixtureInterface
{
    protected FieldTypeProvider $fieldTypeProvider;
    private AttributeTypeRegistry $typeRegistry;

    public function __construct(FieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    public function setTypeRegistry(AttributeTypeRegistry $typeRegistry)
    {
        $this->typeRegistry = $typeRegistry;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return FieldConfigModel::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntity($key)
    {
        return new FieldConfigModel();
    }

    /**
     * {@inheritDoc}
     */
    public function getData()
    {
        $types = $this->fieldTypeProvider->getSupportedFieldTypes();
        $objects = [];

        foreach ($types as $type) {
            $entity = new FieldConfigModel();
            $this->fillEntityData($type, $entity);

            $objects[] = $entity;
        }

        return new \ArrayIterator($objects);
    }

    /**
     * {@inheritDoc}
     */
    public function fillEntityData($key, $entity)
    {
        if (!$entity instanceof FieldConfigModel) {
            return;
        }

        /** @var FieldConfigModel $entity */
        $entity
            ->setType($key)
            ->setFieldName('field_' .$key);

        foreach ($this->fieldTypeProvider->getFieldProperties($key) as $scope => $properties) {
            $values = [];
            foreach ($properties as $propertyName => $config) {
                if (!isset($config['import_export']['import_template']['value'])) {
                    continue;
                }
                $value = $config['import_export']['import_template']['value'];
                if ($scope === 'attribute') {
                    $this->fillAttributeScopeValue(
                        $values,
                        $entity,
                        $propertyName,
                        $value
                    );
                } elseif (\is_array($value)) {
                    $this->fillArrayValue($values, $value, $propertyName);
                } else {
                    if (\is_string($value)) {
                        $value = str_replace('*type*', $key, $value);
                    }
                    $values[$propertyName] = $value;
                }
            }
            if (count($values)) {
                $entity->fromArray($scope, $values, []);
            }
        }
    }

    private function fillArrayValue(array &$scopeValues, array $valueData, string $propertyName): void
    {
        foreach ($valueData as $index => $data) {
            if (\is_array($data)) {
                foreach ($data as $parameterName => $value) {
                    $scopeValues[sprintf('%s.%s.%s', $propertyName, $index, $parameterName)] = $value;
                }
            } else {
                $scopeValues[sprintf('%s.%s', $propertyName, $index)] = $data;
            }
        }
    }

    private function fillAttributeScopeValue(
        array &$scopeValues,
        FieldConfigModel $entity,
        string $propertyName,
        mixed $value
    ): void {
        $attributeType = $this->typeRegistry->getAttributeType($entity);
        if ($attributeType) {
            if ($propertyName === 'searchable' && $attributeType->isSearchable($entity)) {
                $scopeValues[$propertyName] = $value;
            }

            if (\in_array($propertyName, ['filterable', 'filter_by']) && $attributeType->isFilterable($entity)) {
                $scopeValues[$propertyName] = $value;
            }

            if ($propertyName === 'sortable' && $attributeType->isSortable($entity)) {
                $scopeValues[$propertyName] = $value;
            }
        }
    }

    /**
     * @param array $config
     * @return string
     * @deprecated
     */
    protected function getValueFromConfig(array $config)
    {
        $value = null;

        if (isset($config['options']['default_value'])) {
            $value = $config['options']['default_value'];
        }

        if (!$value && isset($config['form']['options']['choices'])) {
            $value = strtolower(reset($config['form']['options']['choices']));
        }

        if (!$value && isset($config['form']['type']) && $config['form']['type'] === 'integer') {
            $value = rand(1, 20);
        }

        return $value;
    }

    /**
     * @param int $enumCount
     * @return array
     * @deprecated
     */
    protected function getEnumValues($enumCount = 2)
    {
        $values = [];

        for ($i = 0; $i <= $enumCount; $i++) {
            $values['enum_options.' . $i . '.label'] = 'enum_label_' . $i;
            $values['enum_options.' . $i . '.is_default'] = $i ? 'no' : 'yes';
        }

        return $values;
    }
}
