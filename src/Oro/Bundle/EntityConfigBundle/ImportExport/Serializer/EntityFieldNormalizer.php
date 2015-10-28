<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class EntityFieldNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_ENUM = 'enum';

    const CONFIG_TYPE = 'value_type';
    const CONFIG_DEFAULT= 'default_value';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    /**
     * @param ManagerRegistry $registry
     */
    public function setRegistry(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param ConfigManager $configManager
     */
    public function setConfigManager(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param FieldTypeProvider $fieldTypeProvider
     */
    public function setFieldTypeProvider(FieldTypeProvider $fieldTypeProvider)
    {
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_a($type, 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel', true);
    }

    /**
     * {@inheritdoc}
     */
    public function supportsNormalization($data, $format = null, array $context = [])
    {
        return $data instanceof FieldConfigModel;
    }

    /**
     * {@inheritdoc}
     */
    public function normalize($object, $format = null, array $context = [])
    {
        if (!$object instanceof FieldConfigModel) {
            return null;
        }

        if (!empty($context['mode']) && $context['mode'] === 'short') {
            return ['id' => $object->getId()];
        }

        $result = [
            'id' => $object->getId(),
            'fieldName' => $object->getFieldName(),
            'type' => $object->getType(),
        ];

        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();
            $values = $object->toArray($scope);

            foreach ($values as $code => $value) {
                $result[sprintf('%s.%s', $scope, $code)] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($data['fieldName']) || !isset($data['type'])) {
            return null;
        }

        $fieldName = $data['fieldName'];
        $fieldType = $data['type'];

        $supportedTypes = $this->fieldTypeProvider->getSupportedFieldTypes();

        if (!in_array($fieldType, $supportedTypes, true)) {
            return false;
        }

        $configOptions = [];
        foreach ($data as $key => $value) {
            $this->extractAndAppendKeyValue($configOptions, $key, $value);
        }

        $options = $this->getDefaultScopeOptions();

        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($fieldType);
        foreach ($fieldProperties as $scope => $properties) {
            $this->updateScopeOptions($options, $configOptions, $scope, $properties);
        }

        $entity = $this->getEntityConfigModel($data['entity']['id']);

        $field = $this->configManager->createConfigFieldModel($entity->getClassName(), $fieldName, $fieldType);

        $this->updateFieldConfig($field, $options);

        $field->setCreated(new \DateTime());
        $field->setUpdated(new \DateTime());

        return $field;
    }

    /**
     * @param int $entityId
     * @return EntityConfigModel
     */
    protected function getEntityConfigModel($entityId)
    {
        $entityClassName = 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel';

        return $this->registry->getManagerForClass($entityClassName)->find($entityClassName, $entityId);
    }

    /**
     * @param array $options
     * @param array $configOptions
     * @param string $scope
     * @param array $properties
     */
    protected function updateScopeOptions(&$options, $configOptions, $scope, $properties)
    {
        foreach ($properties as $code => $config) {
            if (!array_key_exists($scope, $configOptions) || !array_key_exists($code, $configOptions[$scope])) {
                continue;
            }

            if (!isset($options[$scope])) {
                $options[$scope] = [];
            }

            $importOptions = isset($config['options']) ? $config['options'] : [];

            $options[$scope][$code] = $this->denormalizeFieldValue($importOptions, $configOptions[$scope][$code]);
        }
    }

    /**
     * @param FieldConfigModel $field
     * @param array $options
     */
    protected function updateFieldConfig(FieldConfigModel $field, array $options)
    {
        foreach ($options as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $config         = $configProvider->getConfig($field->getEntity()->getClassName(), $field->getFieldName());
            $indexedValues  = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
            $field->fromArray($scope, $scopeValues, $indexedValues);
        }
    }

    /**
     * @param array $config
     * @param mixed $value
     * @return mixed
     */
    public function denormalizeFieldValue($config, $value)
    {
        if (!isset($config[self::CONFIG_TYPE])) {
            return $value;
        }

        if ($value === null && array_key_exists(self::CONFIG_DEFAULT, $config)) {
            return $config[self::CONFIG_DEFAULT];
        }

        switch ($config[self::CONFIG_TYPE]) {
            case self::TYPE_BOOLEAN:
                return $this->normalizeBoolValue($value);

            case self::TYPE_INTEGER:
                return $this->normalizeIntegerValue($value);

            case self::TYPE_ENUM:
                return $this->normalizeEnumValue($value);

            case self::TYPE_STRING:
            default:
                return $this->normalizeStringValue($value);
        }
    }

    /**
     * @param mixed $value
     * @return int
     */
    protected function normalizeIntegerValue($value)
    {
        return (int)$value;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function normalizeStringValue($value)
    {
        return (string)$value;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function normalizeBoolValue($value)
    {
        $lvalue = strtolower($value);
        if (in_array($lvalue, ['yes', 'no', 'true', 'false'])) {
            $value = str_replace(['yes', 'no', 'true', 'false'], [true, false, true, false], $lvalue);
        }

        return (bool)$value;
    }

    /**
     * @param mixed $value
     * @return array
     */
    protected function normalizeEnumValue($value)
    {
        $updatedValue = [];
        foreach ($value as $key => $subvalue) {
            $updatedValue[$key] = [];
            foreach ($this->getEnumConfig() as $subfield => $subconfig) {
                $updatedValue[$key][$subfield]= $this->denormalizeFieldValue($subconfig, $subvalue[$subfield]);
            }
        }

        return $updatedValue;
    }

    /**
     * @param array $array
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    protected function extractAndAppendKeyValue(&$array, $key, $value)
    {
        if (false === strpos($key, '.')) {
            return false;
        }

        $parts = explode('.', $key);

        $current = &$array;
        foreach ($parts as $part) {
            if (!isset($current[$part])) {
                $current[$part] = [];
            }
            $current = &$current[$part];
        }
        $current = $value;

        return true;
    }

    /**
     * @return array
     */
    protected function getDefaultScopeOptions()
    {
        return [
            'extend' => [
                'owner'     => ExtendScope::OWNER_CUSTOM,
                'state'     => ExtendScope::STATE_NEW,
                'origin'    => ExtendScope::ORIGIN_CUSTOM,
                'is_extend' => true,
                'is_deleted' => false,
                'is_serialized' => false,
            ]
        ];
    }

    /**
     * @return array
     */
    protected function getEnumConfig()
    {
        return [
            'label' => [
                self::CONFIG_TYPE => self::TYPE_STRING
            ],
            'is_default' => [
                self::CONFIG_TYPE => self::TYPE_BOOLEAN,
                self::CONFIG_DEFAULT => false,
            ],
        ];
    }
}
