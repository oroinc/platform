<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;

class ConfigExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_entity_config', [$this, 'getClassConfig']),
            new \Twig_SimpleFunction('oro_entity_config_value', [$this, 'getClassConfigValue']),
            new \Twig_SimpleFunction('oro_field_config', [$this, 'getFieldConfig']),
            new \Twig_SimpleFunction('oro_field_config_value', [$this, 'getFieldConfigValue']),
            new \Twig_SimpleFunction('oro_entity_route', [$this, 'getClassRoute']),
            new \Twig_SimpleFunction('oro_entity_metadata_value', [$this, 'getClassMetadataValue']),
        ];
    }

    /**
     * @param string $className The entity class name
     * @param string $scope     The entity config scope name
     *
     * @return array
     */
    public function getClassConfig($className, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className)) {
            return [];
        }

        $entityConfig = new EntityConfigId($scope, $className);

        return $this->configManager->getConfig($entityConfig)->all();
    }

    /**
     * @param string $className The entity class name
     * @param string $attrName  The entity config attribute name
     * @param string $scope     The entity config scope name
     *
     * @return mixed
     */
    public function getClassConfigValue($className, $attrName, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $entityConfig = new EntityConfigId($scope, $className);

        return $this->configManager->getConfig($entityConfig)->get($attrName);
    }

    /**
     * @param string $className The entity class name
     * @param string $fieldName The entity field name
     * @param string $scope     The entity config scope name
     * @return array
     */
    public function getFieldConfig($className, $fieldName, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className, $fieldName)) {
            return [];
        }

        return $this->configManager->getProvider($scope)->getConfig($className, $fieldName)->all();
    }

    /**
     * @param string $className The entity class name
     * @param string $fieldName The entity field name
     * @param string $attrName  The entity config attribute name
     * @param string $scope     The entity config scope name
     * @return array
     */
    public function getFieldConfigValue($className, $fieldName, $attrName, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className, $fieldName)) {
            return null;
        }

        return $this->configManager->getProvider($scope)->getConfig($className, $fieldName)->get($attrName);
    }

    /**
     * @param string $className
     * @param string $attrName
     * @return mixed
     */
    public function getClassMetadataValue($className, $attrName)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        if (!isset($this->configManager->getEntityMetadata($className)->{$attrName})) {
            return null;
        }

        return $this->configManager->getEntityMetadata($className)->{$attrName};
    }

    /**
     * @param string $className The entity class name
     * @param string $routeType Route Type
     * @param bool   $strict    Should exception be thrown if no route of given type found
     *
     * @return string
     */
    public function getClassRoute($className, $routeType = 'view', $strict = false)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        return $this->configManager->getEntityMetadata($className)->getRoute($routeType, $strict);
    }
}
