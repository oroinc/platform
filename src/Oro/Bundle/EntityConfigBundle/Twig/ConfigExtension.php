<?php

namespace Oro\Bundle\EntityConfigBundle\Twig;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\ConfigBundle\Exception\UnexpectedTypeException;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Tools\EntityClassNameHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Psr\Container\ContainerInterface;
use Symfony\Component\Routing\Exception\ExceptionInterface as RoutingException;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Provides Twig functions to work with entity configs:
 *   - oro_entity_config
 *   - oro_entity_config_value
 *   - oro_field_config
 *   - oro_field_config_value
 *   - oro_entity_route
 *   - oro_entity_metadata_value
 *   - oro_entity_view_link
 *   - oro_entity_object_view_link
 */
class ConfigExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;
    private ?ConfigManager $configManager = null;
    private ?RouterInterface $router = null;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('oro_entity_config', [$this, 'getClassConfig']),
            new TwigFunction('oro_entity_config_value', [$this, 'getClassConfigValue']),
            new TwigFunction('oro_field_config', [$this, 'getFieldConfig']),
            new TwigFunction('oro_field_config_value', [$this, 'getFieldConfigValue']),
            new TwigFunction('oro_entity_route', [$this, 'getClassRoute']),
            new TwigFunction('oro_entity_metadata_value', [$this, 'getClassMetadataValue']),
            new TwigFunction('oro_entity_view_link', [$this, 'getViewLink']),
            new TwigFunction('oro_entity_object_view_link', [$this, 'getEntityViewLink']),
        ];
    }

    public function getFilters()
    {
        return [new TwigFilter('render_oro_entity_config_value', [$this, 'renderValue'])];
    }

    public function renderValue($value)
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        return $value;
    }

    /**
     * @param string $className The entity class name
     * @param string $scope     The entity config scope name
     *
     * @return array
     */
    public function getClassConfig($className, $scope = 'entity')
    {
        if (!$className) {
            return [];
        }

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
        if (!$className) {
            return null;
        }

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
        if (!$className) {
            return [];
        }

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
        if (!$className) {
            return null;
        }

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
        if (!$className) {
            return null;
        }

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
        if (!$className) {
            return null;
        }

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
    private function hasRoute($routeName)
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
     * @param object $entity
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

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            ConfigManager::class,
            EntityClassNameHelper::class,
            RouterInterface::class,
            DoctrineHelper::class,
        ];
    }

    private function getConfigManager(): ConfigManager
    {
        if (null === $this->configManager) {
            $this->configManager = $this->container->get(ConfigManager::class);
        }

        return $this->configManager;
    }

    private function getEntityClassNameHelper(): EntityClassNameHelper
    {
        return $this->container->get(EntityClassNameHelper::class);
    }

    private function getRouter(): RouterInterface
    {
        if (null === $this->router) {
            $this->router = $this->container->get(RouterInterface::class);
        }

        return $this->router;
    }

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->container->get(DoctrineHelper::class);
    }
}
