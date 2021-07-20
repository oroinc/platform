<?php

namespace Oro\Bundle\EntityConfigBundle\Provider;

use Doctrine\Common\Util\ClassUtils;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;

/**
 * The configuration provider can be used to get configuration data inside particular configuration scope.
 */
class ConfigProvider
{
    /** @var ConfigManager */
    private $configManager;

    /** @var string */
    private $scope;

    /** @var PropertyConfigBag */
    private $configBag;

    /**
     * @param ConfigManager     $configManager The configuration manager
     * @param string            $scope         The configuration scope this provider works with
     * @param PropertyConfigBag $configBag     A bag contains the configuration of all scopes
     */
    public function __construct(ConfigManager $configManager, string $scope, PropertyConfigBag $configBag)
    {
        $this->scope = $scope;
        $this->configManager = $configManager;
        $this->configBag = $configBag;
    }

    /**
     * Gets a configuration of the scope this provider works with.
     */
    public function getPropertyConfig(): PropertyConfigContainer
    {
        return $this->configBag->getPropertyConfig($this->scope);
    }

    /**
     * Gets the configuration manager.
     */
    public function getConfigManager(): ConfigManager
    {
        return $this->configManager;
    }

    /**
     * Gets an instance of FieldConfigId or EntityConfigId depends on the given parameters.
     */
    public function getId(
        string $className = null,
        string $fieldName = null,
        string $fieldType = null
    ): ConfigIdInterface {
        if ($className) {
            $className = ClassUtils::getRealClass($className);
        }

        if ($fieldName) {
            if ($fieldType) {
                return new FieldConfigId($this->scope, $className, $fieldName, $fieldType);
            }

            return $this->configManager->getId($this->scope, $className, $fieldName);
        }

        return new EntityConfigId($this->scope, $className);
    }

    /**
     * Determines if this provider has configuration data for the given entity or field.
     */
    public function hasConfig(string $className, string $fieldName = null): bool
    {
        return $this->configManager->hasConfig(ClassUtils::getRealClass($className), $fieldName);
    }

    /**
     * Determines if this provider has configuration data for an entity or a field represents the given id.
     */
    public function hasConfigById(ConfigIdInterface $configId): bool
    {
        if ($configId instanceof FieldConfigId) {
            return $this->configManager->hasConfig($configId->getClassName(), $configId->getFieldName());
        }

        return $this->configManager->hasConfig($configId->getClassName());
    }

    /**
     * Gets configuration data for the given entity or field.
     */
    public function getConfig(string $className = null, string $fieldName = null): ConfigInterface
    {
        if ($className) {
            $className = ClassUtils::getRealClass($className);
        }

        if ($fieldName) {
            return $this->configManager->getFieldConfig($this->scope, $className, $fieldName);
        }

        if ($className) {
            return $this->configManager->getEntityConfig($this->scope, $className);
        }

        return $this->configManager->createEntityConfig($this->scope);
    }

    /**
     * Gets configuration data for the given entity or field.
     */
    public function getConfigById(ConfigIdInterface $configId): ConfigInterface
    {
        if ($configId instanceof FieldConfigId) {
            return $this->configManager->getFieldConfig(
                $this->scope,
                $configId->getClassName(),
                $configId->getFieldName()
            );
        }

        $className = $configId->getClassName();
        if ($className) {
            return $this->configManager->getEntityConfig($this->scope, $className);
        }

        return $this->configManager->createEntityConfig($this->scope);
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
    public function getIds(string $className = null, bool $withHidden = false): array
    {
        if ($className) {
            $className = ClassUtils::getRealClass($className);
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
    public function getConfigs(string $className = null, bool $withHidden = false): array
    {
        if ($className) {
            $className = ClassUtils::getRealClass($className);
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
    public function map($callback, string $className = null, bool $withHidden = false): array
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
    public function filter($callback, string $className = null, bool $withHidden = false): array
    {
        return array_filter($this->getConfigs($className, $withHidden), $callback);
    }

    /**
     * Gets the name of the scope this provider works with.
     */
    public function getScope(): string
    {
        return $this->scope;
    }
}
