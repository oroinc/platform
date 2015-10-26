<?php

namespace Oro\Bundle\EntityExtendBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;

class FieldTypeProvider
{
    const GROUP_FIELDS = 'fields';
    const GROUP_RELATIONS = 'relations';

    /** @var ConfigManager */
    protected $configManager;

    /** @var array */
    protected $types = [];

    /**
     * @param ConfigManager $configManager
     * @param array $fields
     * @param array $relations
     */
    public function __construct(ConfigManager $configManager, array $fields = [], array $relations = [])
    {
        $this->configManager = $configManager;
        $this->types = [self::GROUP_FIELDS => $fields, self::GROUP_RELATIONS => $relations];
    }

    /**
     * @return array
     */
    public function getSupportedFieldTypes()
    {
        return $this->types[self::GROUP_FIELDS];
    }

    /**
     * @return array
     */
    public function getSupportedRelationTypes()
    {
        return $this->types[self::GROUP_FIELDS];
    }

    /**
     * @param string $fieldType
     * @param string $configType
     * @return array
     */
    public function getFieldProperties($fieldType, $configType = PropertyConfigContainer::TYPE_FIELD)
    {
        $properties = [];

        foreach ($this->configManager->getProviders() as $provider) {
            $propertyConfig = $provider->getPropertyConfig();

            if ($propertyConfig->hasForm($configType, $fieldType)) {
                $items = $propertyConfig->getFormItems($configType, $fieldType);
                $scope = $provider->getScope();

                foreach ($items as $code => $config) {
                    if (!isset($properties[$scope][$code])) {
                        $properties[$scope][$code] = $config;
                    }
                }
            }
        }

        return $properties;
    }

    /**
     * @param array $config
     * @param mixed $value
     * @return mixed
     */
    public function denormalizeFieldValue($config, $value)
    {
        if (!isset($config['type'])) {
            return $value;
        }

        if ($value === null && isset($config['default'])) {
            $value = $config['default'];
        }

        switch ($config['type']) {
            case 'boolean':
                $lvalue = strtolower($value);
                if (in_array($lvalue, ['yes', 'no', 'true', 'false'])) {
                    $value = str_replace(['yes', 'no', 'true', 'false'], [true, false, true, false], $lvalue);
                }

                return (bool)$value;

            case 'integer':
                return (int)$value;

            case 'array':
                if (!isset($config['items'])) {
                    return $value;
                }

                $items = $config['items'];
                foreach ($value as $key => $subvalue) {
                    foreach ($items as $subfield => $subconfig) {
                        $value[$key][$subfield]= $this->denormalizeFieldValue($subconfig, $value[$key][$subfield]);
                    }
                }
                return $value;
            case 'string':
            default:
                return (string)$value;
        }
    }
}
