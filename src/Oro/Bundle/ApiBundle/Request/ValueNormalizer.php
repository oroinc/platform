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
        bool $isRangeAllowed = false
    ): mixed {
        if (!isset($this->cachedData[$dataType])) {
            return $this->getNormalizedValue($dataType, $requestType, $value, $isArrayAllowed, $isRangeAllowed);
        }

        $cacheKey = (string)$value . '|' . $this->buildCacheKey($requestType, $isArrayAllowed, $isRangeAllowed);
        if (\array_key_exists($cacheKey, $this->cachedData[$dataType])) {
            return $this->cachedData[$dataType][$cacheKey];
        }

        $result = $this->getNormalizedValue($dataType, $requestType, $value, $isArrayAllowed, $isRangeAllowed);

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
     *
     * @return string
     */
    public function getRequirement(
        string $dataType,
        RequestType $requestType,
        bool $isArrayAllowed = false,
        bool $isRangeAllowed = false
    ): string {
        $requirementKey = $dataType . '|' . $this->buildCacheKey($requestType, $isArrayAllowed, $isRangeAllowed);
        if (!\array_key_exists($requirementKey, $this->requirements)) {
            $context = $this->doNormalization($dataType, $requestType, null, $isArrayAllowed, $isRangeAllowed);

            $this->requirements[$requirementKey] = $context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$requirementKey];
    }

    /**
     * {@inheritDoc}
     */
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
        bool $isRangeAllowed
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
        bool $isRangeAllowed
    ): mixed {
        return $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed, $isRangeAllowed)
            ->getResult();
    }

    private function buildCacheKey(RequestType $requestType, bool $isArrayAllowed, bool $isRangeAllowed): string
    {
        $result = (string)$requestType;
        if ($isArrayAllowed) {
            $result .= '|[]';
        }
        if ($isRangeAllowed) {
            $result .= '|..';
        }

        return $result;
    }
}
