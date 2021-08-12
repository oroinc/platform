<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\DBAL\Types\Types;
use Oro\DBAL\Types\MoneyType;
use Oro\DBAL\Types\PercentType;

/**
 * Converts value of the supported scalar field types from the current scalar representation
 * to the field type representation
 */
class ScalarFieldDenormalizer implements ScalarFieldDenormalizerInterface
{
    /**
     * Tells denormalizer to convert all values except null according to the given type or only with valid value
     *
     * @var string
     */
    public const CONTEXT_OPTION_SKIP_INVALID_VALUE = 'skip_invalid_value';

    /** @var string */
    public const SCALAR_TYPE_INTEGER = 'integer';

    /** @var string */
    public const SCALAR_TYPE_FLOAT = 'float';

    /** @var string */
    public const SCALAR_TYPE_BOOLEAN = 'boolean';

    /** @var array */
    protected $supportedScalarTypes = [
        self::SCALAR_TYPE_INTEGER,
        self::SCALAR_TYPE_FLOAT,
        self::SCALAR_TYPE_BOOLEAN,
    ];

    /**
     * List of fields that will be ignored by denormalizer
     *
     * @var array
     */
    protected $ignoredFields = [];

    /** @var array */
    private $convertTypeMappings = [
        Types::SMALLINT => self::SCALAR_TYPE_INTEGER,
        Types::INTEGER => self::SCALAR_TYPE_INTEGER,
        Types::BIGINT => self::SCALAR_TYPE_INTEGER,
        Types::FLOAT => self::SCALAR_TYPE_FLOAT,
        Types::DECIMAL => self::SCALAR_TYPE_FLOAT,
        MoneyType::TYPE => self::SCALAR_TYPE_FLOAT,
        PercentType::TYPE => self::SCALAR_TYPE_FLOAT,
        Types::BOOLEAN => self::SCALAR_TYPE_BOOLEAN,
    ];

    public function addFieldToIgnore(string $className, string $fieldName)
    {
        $ignoredFieldKey = $this->getIgnoredFieldKey($className, $fieldName);
        $this->ignoredFields[$ignoredFieldKey] = true;
    }

    public function addConvertTypeMappings(string $doctrineType, string $toType)
    {
        if ($this->isSupportedScalarType($toType)) {
            $this->convertTypeMappings[$doctrineType] = $toType;
        }
    }

    public function isSupportedScalarType(string $toType): bool
    {
        return \in_array($toType, $this->supportedScalarTypes, true);
    }

    /**
     * Checks whether the given type
     *
     * @param mixed  $data Data to denormalize from.
     * @param string $type Field type
     * @param string $format The format being deserialized from.
     * @param array  $context options available to the denormalizer
     *
     * @return bool
     */
    public function supportsDenormalization($data, string $type, string $format = null, array $context = []): bool
    {
        if (!\array_key_exists('fieldName', $context) || !\array_key_exists('className', $context)) {
            return false;
        }

        $ignoredFieldKey = $this->getIgnoredFieldKey($context['className'], $context['fieldName']);
        if (\array_key_exists($ignoredFieldKey, $this->ignoredFields)) {
            return false;
        }

        if (!\is_scalar($data)) {
            return false;
        }

        return \array_key_exists($type, $this->convertTypeMappings);
    }

    /**
     * {@inheritdoc}
     */
    public function denormalize($data, string $type, string $format = null, array $context = [])
    {
        if ('' === $data) {
            return $data;
        }

        $skipInvalidValue = $context[self::CONTEXT_OPTION_SKIP_INVALID_VALUE] ?? false;
        $toType = $this->convertTypeMappings[$type];

        switch ($toType) {
            case self::SCALAR_TYPE_INTEGER:
                return $this->denormalizeInteger($data, $skipInvalidValue);
            case self::SCALAR_TYPE_FLOAT:
                return $this->denormalizeFloat($data, $skipInvalidValue);
            case self::SCALAR_TYPE_BOOLEAN:
                return $this->denormalizeBoolean($data, $skipInvalidValue);
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param bool $skipInvalidValue
     * @return mixed
     */
    protected function denormalizeInteger($data, bool $skipInvalidValue)
    {
        if (!$skipInvalidValue) {
            return (int) $data;
        }

        $filtered = filter_var($data, FILTER_VALIDATE_INT);
        if (is_int($filtered)) {
            return $filtered;
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param bool $skipInvalidValue
     *
     * @return mixed
     */
    protected function denormalizeFloat($data, bool $skipInvalidValue)
    {
        if (!$skipInvalidValue) {
            return (float)$data;
        }

        $filtered = filter_var($data, FILTER_VALIDATE_FLOAT);
        if (is_float($filtered)) {
            return $filtered;
        }

        return $data;
    }

    /**
     * @param mixed $data
     * @param bool $skipInvalidValue
     *
     * @return mixed
     */
    protected function denormalizeBoolean($data, bool $skipInvalidValue)
    {
        if (!$skipInvalidValue) {
            return (bool)$data;
        }

        if (is_bool($data)) {
            return $data;
        }

        $filtered = filter_var($data, FILTER_VALIDATE_INT);
        if (is_int($filtered)) {
            return (bool)$filtered;
        }

        $filtered = filter_var($data, FILTER_VALIDATE_BOOLEAN);
        if (is_bool($filtered)) {
            return $filtered;
        }

        return $data;
    }

    private function getIgnoredFieldKey(string $className, string $fieldName): string
    {
        return $className . ':' . $fieldName;
    }
}
