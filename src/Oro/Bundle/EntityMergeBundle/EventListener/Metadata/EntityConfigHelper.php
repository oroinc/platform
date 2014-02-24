<?php

namespace Oro\Bundle\EntityMergeBundle\EventListener\Metadata;

use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProviderInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendConfigDumper;

use Oro\Bundle\EntityMergeBundle\Metadata\FieldMetadata;

class EntityConfigHelper
{
    const EXTEND_CONFIG_SCOPE = 'extend';
    const EXTEND_FIELD_PREFIX = ExtendConfigDumper::FIELD_PREFIX;

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
        if ($fieldName && $this->isExtendField($className, $fieldName)) {
            $fieldName = $this->getExtendFieldName($fieldName);
        }

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
            $fieldName = $this->getExtendFieldName($fieldName);
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
        $extendFieldName = $this->getExtendFieldName($fieldName);

        if ($extendFieldName) {
            $extendConfig = $this->getExtendConfigProvider();
            return $extendConfig->hasConfig($className, $extendFieldName) &&
                $extendConfig->getConfig($className, $extendFieldName)->is('is_extend');
        }

        return false;
    }

    /**
     * Removes prefix ExtendConfigDumper::FIELD_PREFIX from the name of field.
     *
     * @param string $fieldName
     * @return string|null
     */
    protected function getExtendFieldName($fieldName)
    {
        if (0 !== strpos($fieldName, self::EXTEND_FIELD_PREFIX)) {
            return null;
        }

        return substr($fieldName, strlen(self::EXTEND_FIELD_PREFIX));
    }

    /**
     * @return ConfigProviderInterface
     */
    protected function getExtendConfigProvider()
    {
        return $this->configManager->getProvider(self::EXTEND_CONFIG_SCOPE);
    }
}
