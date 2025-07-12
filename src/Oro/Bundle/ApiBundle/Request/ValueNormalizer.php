<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;
use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides a way to convert incoming value to concrete data-type.
 */
class ValueNormalizer implements ResetInterface
{
    public const DEFAULT_REQUIREMENT = '.+';

    private ActionProcessorInterface $processor;
    /** @var string[] */
    private array $requirements = [];
    /** @var array the data types that values can be cached in memory */
    private array $cachedData = [
        DataType::ENTITY_TYPE  => [],
        DataType::ENTITY_CLASS => []
    ];

    public function __construct(ActionProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Enables local cache for given data type values.
     * Values of this type should be scalar or objects that can be represented as string (by method __toString).
     */
    public function enableCacheForDataType(string $dataType): void
    {
        $this->cachedData[$dataType] = [];
    }

    /**
     * Converts a value to the given data-type.
     *
     * @param mixed       $value          A value to be converted.
     * @param string      $dataType       The data-type.
     * @param RequestType $requestType    The request type, for example "rest", "soap", etc.
     * @param bool        $isArrayAllowed Whether a value can be an array.
     * @param bool        $isRangeAllowed Whether a value can be a pair of "from" and "to" values.
     * @param array       $options        Additional options that should be used during the value conversion.
     *
     * @return mixed
     *
     * @throws \UnexpectedValueException if the value cannot be converted the given data-type
     */
    public function normalizeValue(
        mixed $value,
        string $dataType,
        RequestType $requestType,
        bool $isArrayAllowed = false,
        bool $isRangeAllowed = false,
        array $options = []
    ): mixed {
        [$dataType, $dataTypeDetail] = $this->normalizeDataType($dataType);
        if ($dataTypeDetail) {
            $options['data_type_detail'] = $dataTypeDetail;
        }

        if (!isset($this->cachedData[$dataType])) {
            return $this->getNormalizedValue(
                $dataType,
                $requestType,
                $value,
                $isArrayAllowed,
                $isRangeAllowed,
                $options
            );
        }

        $cacheKey = $value . '|' . $this->buildCacheKey($requestType, $isArrayAllowed, $isRangeAllowed, $options);
        if (\array_key_exists($cacheKey, $this->cachedData[$dataType])) {
            return $this->cachedData[$dataType][$cacheKey];
        }

        $result = $this->getNormalizedValue(
            $dataType,
            $requestType,
            $value,
            $isArrayAllowed,
            $isRangeAllowed,
            $options
        );

        $this->cachedData[$dataType][$cacheKey] = $result;

        return $result;
    }

    /**
     * Gets a regular expression that can be used to validate a value of the given data-type.
     *
     * @param string      $dataType       The data-type.
     * @param RequestType $requestType    The request type, for example "rest", "soap", etc.
     * @param bool        $isArrayAllowed Whether a value can be an array.
     * @param bool        $isRangeAllowed Whether a value can be a pair of "from" and "to" values.
     * @param array       $options        Additional options that should be used during the value conversion.
     *
     * @return string
     */
    public function getRequirement(
        string $dataType,
        RequestType $requestType,
        bool $isArrayAllowed = false,
        bool $isRangeAllowed = false,
        array $options = []
    ): string {
        [$dataType, $dataTypeDetail] = $this->normalizeDataType($dataType);
        if ($dataTypeDetail) {
            $options['data_type_detail'] = $dataTypeDetail;
        }

        $cacheKey = $dataType . '|' . $this->buildCacheKey($requestType, $isArrayAllowed, $isRangeAllowed, $options);
        if (!\array_key_exists($cacheKey, $this->requirements)) {
            $context = $this->doNormalization(
                $dataType,
                $requestType,
                null,
                $isArrayAllowed,
                $isRangeAllowed,
                $options
            );

            $this->requirements[$cacheKey] = $context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$cacheKey];
    }

    #[\Override]
    public function reset(): void
    {
        $this->requirements = [];
        foreach (array_keys($this->cachedData) as $dataType) {
            $this->cachedData[$dataType] = [];
        }
    }

    /**
     * @throws \UnexpectedValueException if the value cannot be converted the given data-type
     */
    private function doNormalization(
        string $dataType,
        RequestType $requestType,
        mixed $value,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        array $options
    ): NormalizeValueContext {
        /** @var NormalizeValueContext $context */
        $context = $this->processor->createContext();
        $context->getRequestType()->set($requestType);
        $context->setFirstGroup($dataType);
        $context->setLastGroup($dataType);
        $context->setDataType($dataType);
        $context->setResult($value);
        $context->setArrayAllowed($isArrayAllowed);
        $context->setRangeAllowed($isRangeAllowed);
        foreach ($options as $name => $val) {
            $context->addOption($name, $val);
        }
        try {
            $this->processor->process($context);
        } catch (\Exception $e) {
            throw ExceptionUtil::getProcessorUnderlyingException($e);
        }

        return $context;
    }

    /**
     * @throws \UnexpectedValueException if the value cannot be converted the given data-type
     */
    private function getNormalizedValue(
        string $dataType,
        RequestType $requestType,
        mixed $value,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        array $options
    ): mixed {
        return $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed, $isRangeAllowed, $options)
            ->getResult();
    }

    private function normalizeDataType(string $dataType): array
    {
        $dataTypeDetail = null;
        $dataTypeDetailDelimiterPos = strpos($dataType, DataType::DETAIL_DELIMITER);
        if (false !== $dataTypeDetailDelimiterPos) {
            $dataTypeDetail = substr($dataType, $dataTypeDetailDelimiterPos + 1);
            $dataType = substr($dataType, 0, $dataTypeDetailDelimiterPos);
        }

        return [$dataType, $dataTypeDetail];
    }

    private function buildCacheKey(
        RequestType $requestType,
        bool $isArrayAllowed,
        bool $isRangeAllowed,
        array $options
    ): string {
        $result = (string)$requestType;
        if ($isArrayAllowed) {
            $result .= '|[]';
        }
        if ($isRangeAllowed) {
            $result .= '|..';
        }
        if ($options) {
            if (\count($options) > 1) {
                ksort($options);
            }
            foreach ($options as $name => $val) {
                $result .= sprintf('|%s=%s', $name, $val);
            }
        }

        return $result;
    }
}
