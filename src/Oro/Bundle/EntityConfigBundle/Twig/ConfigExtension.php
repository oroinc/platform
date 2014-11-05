<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

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
            new \Twig_SimpleFunction('oro_entity_config_field_value', [$this, 'getClassFieldConfigValue']),
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
     * @param string $fieldName The entity class field name
     * @param string $attrName  The entity config attribute name
     * @param string $scope     The entity config scope name
     *
     * @return mixed
     */
    public function getClassFieldConfigValue($className, $fieldName, $attrName, $scope = 'entity')
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        //$entityConfig = new EntityConfigId($scope, $className);

        $fieldConfigId = $this->configManager->getId($scope, $className, $fieldName);


        return $this->configManager->getConfig($fieldConfigId)->get($attrName);
    }
}
