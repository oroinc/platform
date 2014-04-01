<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\PersistentCollection;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

/**
 * The configuration provider can be used to manage configuration data inside particular configuration scope.
 */
class ConfigProvider implements ConfigProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var PropertyConfigContainer
     */
    protected $propertyConfigContainer;

    /**
     * @var string
     */
    protected $scope;

    /**
     * Constructor.
     *
     * @param ConfigManager      $configManager
     * @param ContainerInterface $container
     * @param string             $scope
     * @param array              $config
     */
    public function __construct(ConfigManager $configManager, ContainerInterface $container, $scope, array $config)
    {
        $this->scope                   = $scope;
        $this->configManager           = $configManager;
        $this->propertyConfigContainer = new PropertyConfigContainer($config, $container);
    }

    /**
     * @return PropertyConfigContainer
     */
    public function getPropertyConfig()
    {
        return $this->propertyConfigContainer;
    }

    /**
     * @return ConfigManager
     */
    public function getConfigManager()
    {
        return $this->configManager;
    }

    /**
     * Gets an instance of FieldConfigId or EntityConfigId depends on the given parameters.
     *
     * @param string|null $className
     * @param string|null $fieldName
     * @param string|null $fieldType
     * @return ConfigIdInterface
     */
    public function getId($className = null, $fieldName = null, $fieldType = null)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        if ($fieldName) {
            if ($fieldType) {
                return new FieldConfigId($this->getScope(), $className, $fieldName, $fieldType);
            } else {
                return $this->configManager->getId($this->getScope(), $className, $fieldName);
            }
        } else {
            return new EntityConfigId($this->getScope(), $className);
        }
    }

    /**
     * Makes a copy of the given configuration id,
     * but sets the scope property of the new id equal to the scope of this configuration provider.
     *
     * @param ConfigIdInterface $configId
     * @return ConfigIdInterface
     */
    public function copyId(ConfigIdInterface $configId)
    {
        if ($configId instanceof FieldConfigId) {
            return $this->getId($configId->getClassName(), $configId->getFieldName(), $configId->getFieldType());
        } else {
            return $this->getId($configId->getClassName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function hasConfig($className, $fieldName = null)
    {
        return $this->configManager->hasConfig($this->getClassName($className), $fieldName);
    }

    /**
     * @param ConfigIdInterface $configId
     * @return bool
     */
    public function hasConfigById(ConfigIdInterface $configId)
    {
        if ($configId instanceof FieldConfigId) {
            return $this->configManager->hasConfig($configId->getClassName(), $configId->getFieldName());
        } else {
            return $this->configManager->hasConfig($configId->getClassName());
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getConfig($className, $fieldName = null)
    {
        return $this->configManager->getConfig($this->getId($className, $fieldName));
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigById(ConfigIdInterface $configId)
    {
        return $this->configManager->getConfig($this->copyId($configId));
    }

    /**
     * Gets a list of ids for all classes (if $className is not specified) or all fields of
     * the given $className, which can be managed by this provider.
     *
     * @param string|null $className
     * @param bool $withHidden
     * @return array|ConfigIdInterface[]
     */
    public function getIds($className = null, $withHidden = false)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        return $this->configManager->getIds($this->getScope(), $className, $withHidden);
    }

    /**
     * Gets configuration data for all classes (if $className is not specified) or all fields of
     * the given $className.
     *
     * @param string|null $className
     * @param bool $withHidden
     * @return array|ConfigInterface[]
     */
    public function getConfigs($className = null, $withHidden = false)
    {
        $result = array();

        foreach ($this->getIds($className, $withHidden) as $configId) {
            $result[] = $this->getConfigById($configId);
        }

        return $result;
    }

    /**
     * Applies the callback to configuration data of all classes (if $className is not specified)
     * or all fields of the given $className.
     *
     * @param callable    $callback The callback function to run for configuration data for each object
     * @param string|null $className
     * @return array|ConfigInterface[]
     */
    public function map(\Closure $callback, $className = null)
    {
        return array_map($callback, $this->getConfigs($className));
    }

    /**
     * {@inheritdoc}
     */
    public function filter($callback, $className = null)
    {
        return array_filter($this->getConfigs($className), $callback);
    }

    /**
     * Gets the real fully-qualified class name of the given object (even if its a proxy).
     *
     * @param string|object|array|PersistentCollection $object
     * @return string
     * @throws RuntimeException
     */
    public function getClassName($object)
    {
        if ($object instanceof PersistentCollection) {
            $className = $object->getTypeClass()->getName();
        } elseif (is_string($object)) {
            $className = ClassUtils::getRealClass($object);
        } elseif (is_object($object)) {
            $className = ClassUtils::getClass($object);
        } elseif (is_array($object) && count($object) && is_object(reset($object))) {
            $className = ClassUtils::getClass(reset($object));
        } else {
            $className = $object;
        }

        if (!is_string($className)) {
            throw new RuntimeException(
                sprintf(
                    'ConfigProvider::getClassName expects Object, ' .
                    'PersistentCollection, array of entities or string. "%s" given',
                    gettype($className)
                )
            );
        }

        return $className;
    }

    /**
     * Removes configuration data for the given object (entity or field) from the cache.
     *
     * @param string      $className
     * @param string|null $fieldName
     */
    public function clearCache($className, $fieldName = null)
    {
        $this->configManager->clearCache($this->getId($className, $fieldName));
    }

    /**
     * Tells the ConfigManager to make the given configuration data managed and persistent.
     *
     * @param ConfigInterface $config
     */
    public function persist(ConfigInterface $config)
    {
        $this->configManager->persist($config);
    }

    /**
     * @param ConfigInterface $config
     */
    public function merge(ConfigInterface $config)
    {
        $this->configManager->merge($config);
    }

    /**
     * Flushes all changes to configuration data that have been queued up to now to the database.
     */
    public function flush()
    {
        $this->configManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getScope()
    {
        return $this->scope;
    }
}
