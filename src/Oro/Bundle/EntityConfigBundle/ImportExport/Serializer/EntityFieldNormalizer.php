<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\DenormalizerInterface;
use Oro\Bundle\ImportExportBundle\Serializer\Normalizer\NormalizerInterface;

class EntityFieldNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const TYPE_BOOLEAN  = 'boolean';
    const TYPE_INTEGER  = 'integer';
    const TYPE_STRING   = 'string';
    const TYPE_ENUM     = 'enum';

    const CONFIG_TYPE       = 'value_type';
    const CONFIG_DEFAULT    = 'default_value';

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

        $configOptions = [];
        foreach ($data as $key => $value) {
            $this->extractAndAppendKeyValue($configOptions, $key, $value);
        }

        $supportedTypes = $this->fieldTypeProvider->getSupportedFieldTypes();

        if (!in_array($fieldType, $supportedTypes, true)) {
            return false;
        }

        $options = [
            'extend' => [
                'owner'     => ExtendScope::OWNER_CUSTOM,
                'state'     => ExtendScope::STATE_NEW,
                'origin'    => ExtendScope::ORIGIN_CUSTOM,
                'is_extend' => true,
                'is_deleted' => false,
                'is_serialized' => false,
            ]
        ];

        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($fieldType);

        foreach ($fieldProperties as $scope => $properties) {
            foreach ($properties as $code => $config) {
                if (array_key_exists($scope, $configOptions) && array_key_exists($code, $configOptions[$scope])) {
                    if (!isset($options[$scope])) {
                        $options[$scope] = [];
                    }

                    $importOptions = isset($config['options']) ? $config['options'] : [];

                    $options[$scope][$code] = $this->denormalizeFieldValue(
                        $importOptions,
                        $configOptions[$scope][$code]
                    );
                }
            }
        }

        $entityClassName = 'Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel';

        /* @var $entity EntityConfigModel */
        $entity = $this->registry->getManagerForClass($entityClassName)->find($entityClassName, $data['entity']['id']);

        $field = $this->configManager->createConfigFieldModel($entity->getClassName(), $fieldName, $fieldType);

        foreach ($options as $scope => $scopeValues) {
            $configProvider = $this->configManager->getProvider($scope);
            $config         = $configProvider->getConfig($entity->getClassName(), $fieldName);
            $indexedValues  = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
            $field->fromArray($scope, $scopeValues, $indexedValues);
        }

        $field->setCreated(new \DateTime());
        $field->setUpdated(new \DateTime());

        return $field;
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
                $lvalue = strtolower($value);
                if (in_array($lvalue, ['yes', 'no', 'true', 'false'])) {
                    $value = str_replace(['yes', 'no', 'true', 'false'], [true, false, true, false], $lvalue);
                }

                return (bool)$value;

            case self::TYPE_INTEGER:
                return (int)$value;

            case self::TYPE_ENUM:
                $updatedValue = [];
                foreach ($value as $key => $subvalue) {
                    foreach ($this->getEnumConfig() as $subfield => $subconfig) {
                        $updatedValue[$key][$subfield]= $this->denormalizeFieldValue($subconfig, $value[$key][$subfield]);
                    }
                }
                return $updatedValue;

            case self::TYPE_STRING:
            default:
                return (string)$value;
        }
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
