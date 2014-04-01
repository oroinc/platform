<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityConfigHelper
{
    const EXTEND_CONFIG_SCOPE = 'extend';

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
     *
     * @param FieldMetadata $fieldMetadata
     */
    public function prepareFieldMetadataPropertyPath(FieldMetadata $fieldMetadata)
    {
        $className = $fieldMetadata->getSourceClassName();
        $fieldName = $fieldMetadata->getSourceFieldName();

        if ($this->isExtendField($className, $fieldName)) {
            $fieldMetadata->set('property_path', $fieldName);
        }
    }

    /**
     * Is field extend.
     *
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    protected function isExtendField($className, $fieldName)
    {
        $extendConfig = $this->getExtendConfigProvider();

        return $extendConfig->hasConfig($className, $fieldName) &&
            $extendConfig->getConfig($className, $fieldName)->is('is_extend');
    }

    /**
     * @return ConfigProviderInterface
     */
    protected function getExtendConfigProvider()
    {
        return $this->configManager->getProvider(self::EXTEND_CONFIG_SCOPE);
    }
}
