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
     * Checks if a config for the given entity/field is immutable.
     *
     * Please take in account that if $constraintName is not specified this method returns TRUE
     * only if all constraints are applied (in other words immutable equals TRUE),
     * FALSE is returned if there are no any restrictions or only part of constraints are applied.
     * More details can be found in corresponding entity_config.yml.
     *
     * To check a particular constraint you can use $constraintName parameter.
     *
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     * @param string|null $constraintName
     *
     * @return bool
     */
    public function isImmutable($scope, $className, $fieldName = null, $constraintName = null)
    {
        $immutable = $this->getImmutable($scope, $className, $fieldName);

        if (!empty($constraintName) && is_array($immutable)) {
            return in_array($constraintName, $immutable);
        }

        return $immutable === true;
    }

    /**
     * Returns a value of 'immutable' attribute for the given entity/field
     *
     * @param string      $scope
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return mixed The returned value depends on a scope, for example in some scopes it can be only boolean,
     *               but other scopes can allow to use either boolean or array. More details can be found
     *               in corresponding entity_config.yml
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
