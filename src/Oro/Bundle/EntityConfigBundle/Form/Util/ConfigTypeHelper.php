<?php

namespace Oro\Bundle\EntityConfigBundle\Form\Util;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

class ConfigTypeHelper
{
    /** @var ConfigManager */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Returns a field name from the given config identifier if it represents a field
     *
     * @param ConfigIdInterface $configId
     *
     * @return string|null A field name if $configId represents a field; otherwise, null
     */
    public function getFieldName(ConfigIdInterface $configId)
    {
        if ($configId instanceof FieldConfigId) {
            return $configId->getFieldName();
        }

        return null;
    }

    /**
     * Returns a field type from the given config identifier if it represents a field
     *
     * @param ConfigIdInterface $configId
     *
     * @return string|null A field type if $configId represents a field; otherwise, null
     */
    public function getFieldType(ConfigIdInterface $configId)
    {
        if ($configId instanceof FieldConfigId) {
            return $configId->getFieldType();
        }

        return null;
    }

    /**
     * Checks if a config for the given entity/field is immutable
     *
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    public function isImmutable($scope, $className, $fieldName = null)
    {
        return $this->getImmutable($scope, $className, $fieldName) === true;
    }

    /**
     * Returns a value of 'immutable' attribute for the given entity/field
     *
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return mixed
     */
    public function getImmutable($scope, $className, $fieldName = null)
    {
        $configProvider = $this->configManager->getProvider($scope);
        if ($configProvider->hasConfig($className, $fieldName)) {
            return $configProvider->getConfig($className, $fieldName)->get('immutable');
        }

        return null;
    }
}
