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
        return $this->types[self::GROUP_RELATIONS];
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
}
