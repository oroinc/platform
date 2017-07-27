<?php

namespace Oro\Bundle\EntityConfigBundle\Config;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class ConfigHelper
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return array
     */
    public function getExtendRequireJsModules()
    {
        return $this->configManager
            ->getProvider('extend')
            ->getPropertyConfig()
            ->getRequireJsModules();
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $scope
     * @return ConfigInterface
     */
    public function getEntityConfigByField(FieldConfigModel $fieldConfigModel, $scope)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->getConfig($fieldConfigModel->getEntity()->getClassName());
    }

    /**
     * @param FieldConfigModel $fieldConfigModel
     * @param string $scope
     * @return ConfigInterface
     */
    public function getFieldConfig(FieldConfigModel $fieldConfigModel, $scope)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->getConfig(
            $fieldConfigModel->getEntity()->getClassName(),
            $fieldConfigModel->getFieldName()
        );
    }

    /**
     * @param FieldConfigModel $field
     * @param $scope
     * @param callable $callback
     * @return ConfigInterface[]
     */
    public function filterEntityConfigByField(FieldConfigModel $field, $scope, callable $callback)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->filter($callback, $field->getEntity()->getClassName());
    }

    /**
     * @param FieldConfigModel $fieldModel
     * @param array $options
     */
    public function updateFieldConfigs(FieldConfigModel $fieldModel, $options)
    {
        $className = $fieldModel->getEntity()->getClassName();
        $fieldName = $fieldModel->getFieldName();
        foreach ($options as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $config = $configProvider->getConfig($className, $fieldName);
            $hasChanges = false;
            foreach ($scopeValues as $code => $val) {
                if (!$config->is($code, $val)) {
                    $config->set($code, $val);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $this->configManager->persist($config);
                $indexedValues = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
                $fieldModel->fromArray($config->getId()->getScope(), $config->all(), $indexedValues);
            }
        }
    }

    /**
     * @param EntityConfigModel $entityConfigModel
     * @param string $scope
     * @return ConfigInterface
     */
    public function getEntityConfig(EntityConfigModel $entityConfigModel, $scope)
    {
        $configProvider = $this->configManager->getProvider($scope);

        return $configProvider->getConfig($entityConfigModel->getClassName());
    }

    /**
     * @return array
     */
    public function getNonExtendedEntitiesClasses()
    {
        $configs = $this->configManager->getConfigs('extend');

        $nonExtendedClassNames = [];
        foreach ($configs as $config) {
            if (!$config->is('is_extend')) {
                $nonExtendedClassNames[] = $config->getId()->getClassName();
            }
        }

        return $nonExtendedClassNames;
    }

    /**
     * @param ConfigInterface $extendEntityConfig
     * @param $fieldType
     * @param array $additionalFieldOptions
     * @return array
     */
    public function createFieldOptions(
        ConfigInterface $extendEntityConfig,
        $fieldType,
        array $additionalFieldOptions = []
    ) {
        $fieldOptions = [
            'extend' => [
                'is_extend' => true,
                'origin' => ExtendScope::ORIGIN_CUSTOM,
                'owner' => ExtendScope::OWNER_CUSTOM,
                'state' => ExtendScope::STATE_NEW
            ]
        ];

        $fieldOptions = array_merge_recursive($fieldOptions, $additionalFieldOptions);
        // check if a field type is complex, for example reverse relation or public enum
        $fieldTypeParts = explode('||', $fieldType);
        if (count($fieldTypeParts) > 1) {
            if (in_array($fieldTypeParts[0], ['enum', 'multiEnum'], true)) {
                // enum
                $fieldType = $fieldTypeParts[0];
                $fieldOptions['enum']['enum_code'] = $fieldTypeParts[1];
            } else {
                $relationType = ExtendHelper::getRelationType($fieldTypeParts[0]);
                if ($relationType) {
                    // reverse relation
                    $fieldType = ExtendHelper::getReverseRelationType($relationType);
                    $relationKey = $fieldTypeParts[0];
                    $fieldOptions['extend']['relation_key'] = $relationKey;
                    $relations = $extendEntityConfig->get('relation');
                    $fieldOptions['extend']['target_entity'] = $relations[$relationKey]['target_entity'];
                } else {
                    throw new \InvalidArgumentException(
                        sprintf('The field type "%s" is not supported.', $fieldType)
                    );
                }
            }
        }

        return [$fieldType, $fieldOptions];
    }
}
