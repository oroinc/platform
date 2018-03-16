<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;

class ConfigExtension extends \Twig_Extension
{
    const NAME = 'oro_entity_config';

    /** @var ContainerInterface */
    protected $container;

    /** @var ConfigManager|null */
    private $configManager;

    /** @var RouterInterface|null */
    private $router;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return ConfigManager
     */
    protected function getConfigManager()
    {
        if (null === $this->configManager) {
            $this->configManager = $this->container->get('oro_entity_config.config_manager');
        }

        return $this->configManager;
    }

    /**
     * @return EntityClassNameHelper
     */
    protected function getEntityClassNameHelper()
    {
        return $this->container->get('oro_entity.entity_class_name_helper');
    }

    /**
     * @return RouterInterface
     */
    protected function getRouter()
    {
        if (null === $this->router) {
            $this->router = $this->container->get('router');
        }

        return $this->router;
    }

    /**
     * @return DoctrineHelper
     */
    protected function getDoctrineHelper()
    {
        return $this->container->get('oro_entity.doctrine_helper');
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
            new \Twig_SimpleFunction('oro_entity_object_view_link', [$this, 'getEntityViewLink']),
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
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className)) {
            return [];
        }

        $entityConfig = new EntityConfigId($scope, $className);

        return $configManager->getConfig($entityConfig)->all();
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
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className)) {
            return null;
        }

        $entityConfig = new EntityConfigId($scope, $className);

        return $configManager->getConfig($entityConfig)->get($attrName);
    }

    /**
     * @param string $className The entity class name
     * @param string $fieldName The entity field name
     * @param string $scope     The entity config scope name
     * @return array
     */
    public function getFieldConfig($className, $fieldName, $scope = 'entity')
    {
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className, $fieldName)) {
            return [];
        }

        return $configManager->getProvider($scope)->getConfig($className, $fieldName)->all();
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
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className, $fieldName)) {
            return null;
        }

        return $configManager->getProvider($scope)->getConfig($className, $fieldName)->get($attrName);
    }

    /**
     * @param string $className
     * @param string $attrName
     * @return mixed
     */
    public function getClassMetadataValue($className, $attrName)
    {
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className)) {
            return null;
        }

        if (!isset($configManager->getEntityMetadata($className)->{$attrName})) {
            return null;
        }

        return $configManager->getEntityMetadata($className)->{$attrName};
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
        $configManager = $this->getConfigManager();
        if (!$configManager->hasConfig($className)) {
            return null;
        }

        $entityMetadata = $configManager->getEntityMetadata($className);
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
            $this->getRouter()->generate($routeName);
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
            return $this->getRouter()->generate($route, ['id' => $id]);
        }

        // Generate view link for the custom entity
        if (ExtendHelper::isCustomEntity($className)) {
            return $this->getRouter()->generate(
                'oro_entity_view',
                [
                    'id'         => $id,
                    'entityName' => $this->getEntityClassNameHelper()->getUrlSafeClassName($className)
                ]
            );
        }

        return null;
    }

    /**
     * @param $entity object
     *
     * @return string|null
     */
    public function getEntityViewLink($entity)
    {
        if (!is_object($entity)) {
            throw new UnexpectedTypeException($entity, 'object');
        }

        $className = ClassUtils::getClass($entity);
        $id = $this->getDoctrineHelper()->getSingleEntityIdentifier($entity);

        return $this->getViewLink($className, $id);
    }
}
