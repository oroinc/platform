<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\PersistentCollection;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Exception\RuntimeException;

/**
 * The configuration provider can be used to get configuration data inside particular configuration scope.
 */
class ConfigProvider implements ConfigProviderInterface
{
    /** @var ConfigManager */
    protected $configManager;

    /** @var PropertyConfigContainer */
    protected $propertyConfig;

    /** @var string */
    protected $scope;

    /**
     * @param ConfigManager $configManager The configuration manager
     * @param string        $scope         The configuration scope this provider works with
     * @param array         $config        The scope configuration
     */
    public function __construct(ConfigManager $configManager, $scope, array $config)
    {
        $this->scope          = $scope;
        $this->configManager  = $configManager;
        $this->propertyConfig = new PropertyConfigContainer($config);
    }

    /**
     * Gets a configuration the scope this provider works with.
     *
     * @return PropertyConfigContainer
     */
    public function getPropertyConfig()
    {
        return $this->propertyConfig;
    }

    /**
     * Gets the configuration manager.
     *
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
     *
     * @return ConfigIdInterface
     */
    public function getId($className = null, $fieldName = null, $fieldType = null)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        if ($fieldName) {
            if ($fieldType) {
                return new FieldConfigId($this->scope, $className, $fieldName, $fieldType);
            } else {
                return $this->configManager->getId($this->scope, $className, $fieldName);
            }
        } else {
            return new EntityConfigId($this->scope, $className);
        }
    }

    /**
     * Determines if this provider has configuration data for the given entity or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return bool
     */
    public function hasConfig($className, $fieldName = null)
    {
        return $this->configManager->hasConfig($this->getClassName($className), $fieldName);
    }

    /**
     * Determines if this provider has configuration data for an entity or a field represents the given id.
     *
     * @param ConfigIdInterface $configId
     *
     * @return bool
     */
    public function hasConfigById(ConfigIdInterface $configId)
    {
        return $configId instanceof FieldConfigId
            ? $this->configManager->hasConfig($configId->getClassName(), $configId->getFieldName())
            : $this->configManager->hasConfig($configId->getClassName());
    }

    /**
     * Gets configuration data for the given entity or field.
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @return ConfigInterface
     */
    public function getConfig($className, $fieldName = null)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        if ($fieldName) {
            return $this->configManager->getFieldConfig($this->scope, $className, $fieldName);
        } elseif ($className) {
            return $this->configManager->getEntityConfig($this->scope, $className);
        } else {
            return $this->configManager->createEntityConfig($this->scope);
        }
    }

    /**
     * Gets configuration data for the given entity or field.
     *
     * @param ConfigIdInterface $configId
     *
     * @return ConfigInterface
     */
    public function getConfigById(ConfigIdInterface $configId)
    {
        $className = $configId->getClassName();
        if ($configId instanceof FieldConfigId) {
            return $this->configManager->getFieldConfig($this->scope, $className, $configId->getFieldName());
        } elseif ($className) {
            return $this->configManager->getEntityConfig($this->scope, $className);
        } else {
            return $this->configManager->createEntityConfig($this->scope);
        }
    }

    /**
     * Gets a list of ids for all configurable entities (if $className is not specified)
     * or all configurable fields of the given entity, which can be managed by this provider.
     *
     * @param string|null $className
     * @param bool        $withHidden Set true if you need ids of all configurable entities,
     *                                including entities marked as ConfigModel::MODE_HIDDEN
     *
     * @return ConfigIdInterface[]
     */
    public function getIds($className = null, $withHidden = false)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        return $this->configManager->getIds($this->scope, $className, $withHidden);
    }

    /**
     * Gets configuration data for all configurable entities (if $className is not specified)
     * or all configurable fields of the given entity.
     *
     * @param string|null $className
     * @param bool        $withHidden Set true if you need ids of all configurable entities,
     *                                including entities marked as ConfigModel::MODE_HIDDEN
     *
     * @return ConfigInterface[]
     */
    public function getConfigs($className = null, $withHidden = false)
    {
        if ($className) {
            $className = $this->getClassName($className);
        }

        return $this->configManager->getConfigs($this->scope, $className, $withHidden);
    }

    /**
     * Applies the callback to configuration data to all configurable entities (if $className is not specified)
     * or all configurable fields of the given entity.
     *
     * @param callable    $callback The callback function to run for configuration data for each object
     * @param string|null $className
     * @param bool        $withHidden
     *
     * @return array
     */
    public function map(\Closure $callback, $className = null, $withHidden = false)
    {
        return array_map($callback, $this->getConfigs($className, $withHidden));
    }

    /**
     * Gets configuration data filtered by the given callback of all configurable entities
     * (if $className is not specified) or all fields of the given entities.
     *
     * @param callable    $callback The callback function to run for configuration data for each object
     * @param string|null $className
     * @param bool        $withHidden
     *
     * @return ConfigInterface[]
     */
    public function filter($callback, $className = null, $withHidden = false)
    {
        return array_filter($this->getConfigs($className, $withHidden), $callback);
    }

    /**
     * Gets the real fully-qualified class name of the given object (even if its a proxy).
     *
     * @param string|object|array|PersistentCollection $object
     *
     * @return string
     *
     * @throws RuntimeException if a class name cannot be retrieved
     */
    public function getClassName($object)
    {
        if (is_string($object)) {
            return ClassUtils::getRealClass($object);
        }

        if (is_object($object)) {
            if ($object instanceof PersistentCollection) {
                return $object->getTypeClass()->getName();
            }

            return ClassUtils::getClass($object);
        }

        if (is_array($object) && !empty($object) && is_object(reset($object))) {
            return ClassUtils::getClass(reset($object));
        }

        throw new RuntimeException(
            sprintf(
                'ConfigProvider::getClassName expects Object, ' .
                'PersistentCollection, array of entities or string. "%s" given',
                gettype($object)
            )
        );
    }

    /**
     * Removes configuration data for the given object (entity or field) from the cache.
     *
     * @param string      $className
     * @param string|null $fieldName
     *
     * @deprecated since 1.9. Use ConfigManager::clearCache instead
     */
    public function clearCache($className, $fieldName = null)
    {
        $this->configManager->clearCache($this->getId($className, $fieldName));
    }

    /**
     * Tells the ConfigManager to make the given configuration data managed and persistent.
     *
     * @param ConfigInterface $config
     *
     * @deprecated since 1.9. Use ConfigManager::persist instead
     */
    public function persist(ConfigInterface $config)
    {
        $this->configManager->persist($config);
    }

    /**
     * @param ConfigInterface $config
     *
     * @deprecated since 1.9. Use ConfigManager::merge instead
     */
    public function merge(ConfigInterface $config)
    {
        $this->configManager->merge($config);
    }

    /**
     * Flushes all changes to configuration data that have been queued up to now to the database.
     *
     * @deprecated since 1.9. Use ConfigManager::flush instead
     */
    public function flush()
    {
        $this->configManager->flush();
    }

    /**
     * Gets the name of the scope this provider works with.
     *
     * @return string
     */
    public function getScope()
    {
        return $this->scope;
    }
}
