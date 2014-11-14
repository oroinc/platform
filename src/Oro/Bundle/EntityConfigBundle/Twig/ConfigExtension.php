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
            new \Twig_SimpleFunction('oro_entity_route', [$this, 'getClassRoute']),
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
     * @param string $entityClass The entity class name
     * @param string $routeType   Route Type
     *
     * @return string
     */
    public function getClassRoute($entityClass, $routeType = 'view')
    {
        if (!in_array($routeType, ['view', 'name'])) {
            return null;
        }

        $property = 'route' . ucfirst($routeType);
        $metadata = $this->configManager->getEntityMetadata($entityClass);

        if ($metadata && $metadata->{$property}) {
            return $metadata->{$property};
        }

        return $this->getDefaultClassRoute($entityClass, $routeType);
    }

    /**
     * @param $className
     * @param $routeType
     *
     * @return string
     */
    protected function getDefaultClassRoute($className, $routeType)
    {
        static $routeMap = [
            'view' => 'view',
            'name' => 'index',
        ];
        $postfix = $routeMap[$routeType];
        $parts   = explode('\\', $className);

        return strtolower(reset($parts)) . '_' . strtolower(end($parts)) . '_' . $postfix;
    }
}
