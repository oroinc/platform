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
     * @param AbstractEnumValue $object
     *
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

                    $importOptions = isset($config['import_options']) ? $config['import_options'] : [];

                    $options[$scope][$code] = $this->fieldTypeProvider->denormalizeFieldValue($importOptions, $configOptions[$scope][$code]);
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
}
