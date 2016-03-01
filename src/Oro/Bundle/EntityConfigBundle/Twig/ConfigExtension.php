<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ConfigExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config';

    /** @var ConfigManager */
    protected $configManager;

    /** @var EntityClassNameHelper */
    protected $entityClassNameHelper;

    /** @var RouterInterface */
    private $router;

    /**
     * @param ConfigManager         $configManager
     * @param RouterInterface       $router
     * @param EntityClassNameHelper $entityClassNameHelper
     */
    public function __construct(
        ConfigManager $configManager,
        RouterInterface $router,
        EntityClassNameHelper $entityClassNameHelper
    ) {
        $this->configManager         = $configManager;
        $this->router                = $router;
        $this->entityClassNameHelper = $entityClassNameHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('oro_entity_config', [$this, 'getClassConfig']),
            new \Twig_SimpleFunction('oro_entity_config_value', [$this, 'getClassConfigValue']),
            new \Twig_SimpleFunction('oro_field_config', [$this, 'getFieldConfig']),
            new \Twig_SimpleFunction('oro_field_config_value', [$this, 'getFieldConfigValue']),
            new \Twig_SimpleFunction('oro_entity_route', [$this, 'getClassRoute']),
            new \Twig_SimpleFunction('oro_entity_metadata_value', [$this, 'getClassMetadataValue']),
            new \Twig_SimpleFunction('oro_entity_view_link', [$this, 'getViewLink']),
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
     * @return string|null
     */
    public function getClassRoute($className, $routeType = 'view', $strict = false)
    {
        if (!$this->configManager->hasConfig($className)) {
            return null;
        }

        $entityMetadata = $this->configManager->getEntityMetadata($className);
        if (!$entityMetadata) {
            return null;
        }

        $route = $entityMetadata->getRoute($routeType, $strict);

        return $route && $this->hasRoute($route)
            ? $route
            : null;
    }

    /**
     * @param string $routeName
     *
     * @return bool
     */
    protected function hasRoute($routeName)
    {
        try {
            $this->router->generate($routeName);
        } catch (RouteNotFoundException $e) {
            return false;
        } catch (RoutingException $e) {
            return true;
        }

        return true;
    }

    /**
     * @param string $className The entity class name
     * @param int    $id        The entity id
     *
     * @return string|null
     */
    public function getViewLink($className, $id)
    {
        $route = $this->getClassRoute($className, 'view');
        if ($route) {
            return $this->router->generate($route, ['id' => $id]);
        }

        // Generate view link for the custom entity
        if (ExtendHelper::isCustomEntity($className)) {
            return $this->router->generate(
                'oro_entity_view',
                [
                    'id'         => $id,
                    'entityName' => $this->entityClassNameHelper->getUrlSafeClassName($className)

                ]
            );
        }

        return null;
    }
}
