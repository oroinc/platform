<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

/**
 * Provides access to entity and field configuration for merge operations.
 *
 * Acts as a helper to retrieve configuration from the entity config manager for both
 * entities and fields. It supports querying configuration by scope (e.g., `entity`, `extend`),
 * class name, and field name. Additionally, it provides utilities for preparing field metadata
 * by checking extend configuration and setting appropriate property paths and display flags.
 */
class EntityConfigHelper
{
    public const EXTEND_CONFIG_SCOPE = 'extend';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * Get config by class name and field name.
     *
     * @param string $scope
     * @param string $className
     * @param string $fieldName
     * @return ConfigInterface|null
     */
    public function getConfig($scope, $className, $fieldName)
    {
        $provider = $this->configManager->getProvider($scope);

        $result = null;

        if ($provider->hasConfig($className, $fieldName)) {
            $result = $provider->getConfig($className, $fieldName);
        }

        return $result;
    }

    /**
     * Get config by field metadata.
     *
     * @param string $scope
     * @param FieldMetadata $fieldMetadata
     * @return ConfigInterface|null
     */
    public function getConfigByFieldMetadata($scope, FieldMetadata $fieldMetadata)
    {
        $className = $fieldMetadata->getSourceClassName();
        $fieldName = $fieldMetadata->getSourceFieldName();

        return $this->getConfig($scope, $className, $fieldName);
    }

    /**
     * Prepare metadata field.
     */
    public function prepareFieldMetadataPropertyPath(FieldMetadata $fieldMetadata)
    {
        $className = $fieldMetadata->getSourceClassName();
        $fieldName = $fieldMetadata->getSourceFieldName();

        $extendConfig = $this->getFieldExtendConfig($className, $fieldName);
        if ($extendConfig && $extendConfig->is('is_extend')) {
            $fieldMetadata->set('property_path', $fieldName);
            $fieldMetadata->set('display', true);
        }
    }

    /**
     * @param string $className
     * @param string $fieldName
     *
     * @return ConfigInterface|null
     */
    protected function getFieldExtendConfig($className, $fieldName)
    {
        $extendConfig = $this->getExtendConfigProvider();

        return $extendConfig->hasConfig($className, $fieldName) ?
            $extendConfig->getConfig($className, $fieldName) :
            null;
    }

    /**
     * @return ConfigProvider
     */
    protected function getExtendConfigProvider()
    {
        return $this->configManager->getProvider(self::EXTEND_CONFIG_SCOPE);
    }
}
