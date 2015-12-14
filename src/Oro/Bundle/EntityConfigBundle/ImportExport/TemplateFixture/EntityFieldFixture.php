<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\TemplateFixture;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\ImportExportBundle\TemplateFixture\TemplateFixtureInterface;

class EntityFieldFixture implements TemplateFixtureInterface
{
    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    /**
     * @param FieldTypeProvider $fieldTypeProvider
     */
    public function __construct(FieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity($key)
    {
        return new FieldConfigModel();
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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

            foreach ($properties as $code => $config) {
                if ($scope === 'enum' && $code === 'enum_options') {
                    $values = array_merge($values, $this->getEnumValues());
                } else {
                    $value = $this->getValueFromConfig($config);
                    if (is_bool($value)) {
                        $value = $value ? 'yes' : 'no';
                    }

                    $values[$code] = $value === null ? $code . '_value' : $value;
                }
            }
            $entity->fromArray($scope, $values, []);
        }
    }

    /**
     * @param array $config
     * @return string
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
