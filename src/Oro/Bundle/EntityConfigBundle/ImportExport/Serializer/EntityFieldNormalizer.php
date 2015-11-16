<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Serializer;

use Doctrine\Common\Persistence\ManagerRegistry;

use Symfony\Component\Serializer\Exception\UnexpectedValueException;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
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
     * @param ConfigManager $configManager
     * @param FieldTypeProvider $fieldTypeProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        FieldTypeProvider $fieldTypeProvider
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->fieldTypeProvider = $fieldTypeProvider;
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
        $result = [
            'id' => $object->getId(),
            'fieldName' => $object->getFieldName(),
            'type' => $object->getType(),
        ];

        foreach ($this->configManager->getProviders() as $provider) {
            $scope = $provider->getScope();

            foreach ($object->toArray($scope) as $code => $value) {
                $result[sprintf('%s.%s', $scope, $code)] = $value;
            }
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
    {
        return is_array($data) && is_a($type, 'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel', true);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, $class, $format = null, array $context = [])
    {
        if (!isset($data['type'], $data['fieldName'], $data['entity']['id'])) {
            throw new UnexpectedValueException(
                'Data does not contain required properties: type, fieldType or entity_id'
            );
        }

        $fieldType = $data['type'];
        $fieldName = $data['fieldName'];
        $entity = $this->getEntityConfigModel($data['entity']['id']);

        $fieldModel = new FieldConfigModel($fieldName, $fieldType);
        $fieldModel->setEntity($entity);

        $options = [];
        foreach ($data as $key => $value) {
            $this->extractAndAppendKeyValue($options, $key, $value);
        }

        $this->updateModelConfig($fieldModel, $options);

        return $fieldModel;
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
     * @param FieldConfigModel $model
     * @param array $options
     */
    protected function updateModelConfig(FieldConfigModel $model, array $options)
    {
        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($model->getType());
        foreach ($fieldProperties as $scope => $properties) {
            $values = [];

            foreach ($properties as $code => $config) {
                if (!isset($options[$scope][$code])) {
                    continue;
                }

                $value = $this->denormalizeFieldValue(
                    isset($config['options']) ? $config['options'] : [],
                    $options[$scope][$code],
                    $model->getType()
                );

                if ($value !== null) {
                    $values[$code] = $value;
                }
            }

            $model->fromArray($scope, $values, []);
        }
    }

    /**
     * @param array $config
     * @param mixed $value
     * @param string $fieldType
     * @return mixed
     */
    protected function denormalizeFieldValue(array $config, $value, $fieldType)
    {
        $type = array_key_exists(self::CONFIG_TYPE, $config) ? $config[self::CONFIG_TYPE] : null;

        switch ($type) {
            case self::TYPE_BOOLEAN:
                $result = $this->normalizeBoolValue($value);
                break;
            case self::TYPE_ENUM:
                $result = $this->normalizeEnumValue($value, $fieldType);
                break;
            case self::TYPE_INTEGER:
                $result = (int)$value;
                break;
            default:
                $result = (string)$value;
                break;
        }

        return $result;
    }

    /**
     * @param mixed $value
     * @return bool
     */
    protected function normalizeBoolValue($value)
    {
        return in_array(strtolower($value), ['yes', 'true', '1'], true);
    }

    /**
     * @param mixed $value
     * @param string $type
     * @return array
     */
    protected function normalizeEnumValue($value, $type)
    {
        $default = false;

        $updatedValue = [];
        foreach ($value as $subvalue) {
            // don't allow empty data
            if (0 === count(array_filter($subvalue))) {
                continue;
            }
            $enum = ['id' => null, 'priority' => null];
            foreach ($this->getEnumConfig() as $subfield => $subconfig) {
                $enum[$subfield] = $this->denormalizeFieldValue($subconfig, $subvalue[$subfield], $type);
            }

            $enum['is_default'] = !$default && !empty($enum['is_default']);

            if ($type !== 'multiEnum' && $enum['is_default']) {
                $default = true;
            }

            $updatedValue[] = $enum;
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
    protected function getEnumConfig()
    {
        return [
            'label' => [
                self::CONFIG_TYPE => self::TYPE_STRING
            ],
            'is_default' => [
                self::CONFIG_TYPE => self::TYPE_BOOLEAN
            ],
        ];
    }
}
