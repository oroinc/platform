<?php

namespace Oro\Bundle\ImportExportBundle\Serializer\Normalizer;

use Doctrine\DBAL\Types\Type;
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

    /** @var array */
    protected $supportedScalarTypes = [
        self::SCALAR_TYPE_INTEGER,
        self::SCALAR_TYPE_FLOAT
    ];

    /**
     * List of fields that will be ignored by denormalizer
     *
     * @var array
     */
    protected $ignoredFields = [];

    /** @var array */
    private $convertTypeMappings = [
        Type::SMALLINT => self::SCALAR_TYPE_INTEGER,
        Type::INTEGER => self::SCALAR_TYPE_INTEGER,
        Type::BIGINT => self::SCALAR_TYPE_INTEGER,
        Type::FLOAT => self::SCALAR_TYPE_FLOAT,
        Type::DECIMAL => self::SCALAR_TYPE_FLOAT,
        MoneyType::TYPE => self::SCALAR_TYPE_FLOAT,
        PercentType::TYPE => self::SCALAR_TYPE_FLOAT
    ];

    /**
     * @param string $className
     * @param string $fieldName
     */
    public function addFieldToIgnore(string $className, string $fieldName)
    {
        $ignoredFieldKey = $this->getIgnoredFieldKey($className, $fieldName);
        $this->ignoredFields[$ignoredFieldKey] = true;
    }

    /**
     * @param string $doctrineType
     * @param string $toType
     */
    public function addConvertTypeMappings(string $doctrineType, string $toType)
    {
        if ($this->isSupportedScalarType($toType)) {
            $this->convertTypeMappings[$doctrineType] = $toType;
        }
    }

    /**
     * @param string $toType
     *
     * @return bool
     */
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
    public function supportsDenormalization($data, $type, $format = null, array $context = [])
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
     * @param mixed  $data
     * @param string $type
     * @param null   $format
     * @param array  $context
     *
     * @return mixed
     */
    public function denormalize($data, $type, $format = null, array $context = [])
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

        if (!is_numeric($data)) {
            return $data;
        }

        $originalValueAsInt = (int)$data;
        /** Check that variable is not float and not overflow */
        if ($originalValueAsInt == $data) {
            return $originalValueAsInt;
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
            return (float) $data;
        }

        if (!is_numeric($data)) {
            return $data;
        }

        $originalValueAsFloat = (float)$data;
        /** Check that variable is not float and not overflow */
        if ($originalValueAsFloat == $data) {
            return $originalValueAsFloat;
        }

        return $data;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return string
     */
    private function getIgnoredFieldKey(string $className, string $fieldName): string
    {
        return $className . ':' . $fieldName;
    }
}
