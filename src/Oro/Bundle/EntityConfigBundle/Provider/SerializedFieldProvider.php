<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

class SerializedFieldProvider
{
    /**
     * @var array
     */
    private $serializableTypes = [
        'string',
        'integer',
        'smallint',
        'bigint',
        'boolean',
        'decimal',
        'date',
        'datetime',
        'text',
        'float',
        'money',
        'percent',
        'html_escaped'
    ];

    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @return bool
     */
    public function isSerialized(FieldConfigModel $fieldConfigModel)
    {
        return $this->checkIsSerialiazed($fieldConfigModel, function ($scope, $code) use ($fieldConfigModel) {
            $scopeValues = $fieldConfigModel->toArray($scope);

            return isset($scopeValues[$code]) ? $scopeValues[$code] : null;
        });
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param array $data
     * @return bool
     */
    public function isSerializedByData(FieldConfigModel $fieldConfigModel, array $data = [])
    {
        return $this->checkIsSerialiazed($fieldConfigModel, function ($scope, $code) use ($data) {
            return isset($data[$scope][$code]) ? $data[$scope][$code] : null;
        });
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param callable $getFieldValueCallback
     * @return bool
     */
    private function checkIsSerialiazed(FieldConfigModel $fieldConfigModel, callable $getFieldValueCallback)
    {
        if (!$this->isSerializableType($fieldConfigModel)) {
            return false;
        }

        $propertyValues = $this->getRequiredPropertyValues();
        if (empty($propertyValues)) {
            return false;
        }

        foreach ($propertyValues as $propertyValue) {
            $fieldValue = $getFieldValueCallback($propertyValue['config_id']['scope'], $propertyValue['code']);

            // Please don't change to strict comparison to be able to compare empty data with false value.
            if ($fieldValue != $propertyValue['value']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getSerializableTypes()
    {
        return $this->serializableTypes;
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @return bool
     */
    protected function isSerializableType(FieldConfigModel $fieldConfigModel)
    {
        return in_array($fieldConfigModel->getType(), $this->getSerializableTypes());
    }

    /**
     * @return array
     */
    protected function getRequiredPropertyValues()
    {
        $propertyConfig = $this->extendConfigProvider->getPropertyConfig();
        $requiredPropertyValues = $propertyConfig->getRequiredPropertiesValues(PropertyConfigContainer::TYPE_FIELD);

        return isset($requiredPropertyValues['is_serialized']) ? $requiredPropertyValues['is_serialized'] : [];
    }
}
