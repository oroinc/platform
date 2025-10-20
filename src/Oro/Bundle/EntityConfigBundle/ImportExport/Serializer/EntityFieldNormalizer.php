<?php

namespace Oro\Bundle\EntityConfigBundle\ImportExport\Serializer;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Provider\FieldTypeProvider;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

/**
 * Normalize/denormalize FieldConfigModel instances.
 */
class EntityFieldNormalizer implements NormalizerInterface, DenormalizerInterface
{
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_ENUM = 'enum';

    const CONFIG_TYPE = 'value_type';
    const CONFIG_DEFAULT = 'default_value';

    /** @var ManagerRegistry */
    protected $registry;

    /** @var ConfigManager */
    protected $configManager;

    /** @var FieldTypeProvider */
    protected $fieldTypeProvider;

    public function __construct(
        ManagerRegistry $registry,
        ConfigManager $configManager,
        FieldTypeProvider $fieldTypeProvider
    ) {
        $this->registry = $registry;
        $this->configManager = $configManager;
        $this->fieldTypeProvider = $fieldTypeProvider;
    }

    #[\Override]
    public function supportsNormalization($data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof FieldConfigModel;
    }

    #[\Override]
    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = []
    ): float|int|bool|\ArrayObject|array|string|null {
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

    #[\Override]
    public function supportsDenormalization($data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && is_a($type, FieldConfigModel::class, true);
    }

    #[\Override]
    public function denormalize($data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!isset($data['entity']['id'])) {
            throw new UnexpectedValueException('Data doesn\'t contains entity id');
        }

        $fieldModel = new FieldConfigModel($data['fieldName'] ?? null, $data['type'] ?? null);
        $fieldModel->setEntity($this->getEntityConfigModel($data['entity']['id']));

        $options = [];
        foreach ($data as $key => $value) {
            $this->extractAndAppendKeyValue($options, $key, $value);
        }

        $this->updateModelConfig($fieldModel, $options);

        return $fieldModel;
    }

    protected function getEntityConfigModel(string|int $entityId): ?EntityConfigModel
    {
        return $this->registry->getManagerForClass(EntityConfigModel::class)->find(EntityConfigModel::class, $entityId);
    }

    protected function updateModelConfig(FieldConfigModel $model, array $options): void
    {
        $fieldProperties = $this->fieldTypeProvider->getFieldProperties($model->getType());
        foreach ($fieldProperties as $scope => $properties) {
            $values = [];

            foreach ($properties as $code => $config) {
                if (!isset($options[$scope][$code])) {
                    continue;
                }

                $value = $this->denormalizeFieldValue(
                    $config['options'] ?? [],
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
     *
     * @return mixed
     */
    protected function denormalizeFieldValue(array $config, $value, $fieldType)
    {
        $type = $config[self::CONFIG_TYPE] ?? null;

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
     *
     * @return bool
     */
    protected function normalizeBoolValue($value)
    {
        return in_array(strtolower($value), ['yes', 'true', '1'], true);
    }

    /**
     * @param mixed $value
     * @param string $type
     *
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
                $enum[$subfield] = $this->denormalizeFieldValue($subconfig, $subvalue[$subfield] ?? null, $type);
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
     *
     * @return boolean
     */
    protected function extractAndAppendKeyValue(&$array, $key, $value)
    {
        if (!str_contains($key, '.')) {
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
                self::CONFIG_TYPE => self::TYPE_STRING,
            ],
            'is_default' => [
                self::CONFIG_TYPE => self::TYPE_BOOLEAN,
            ],
        ];
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }
}
