<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Component\ChainProcessor\ActionProcessorInterface;
use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;

/**
 * Provides a way to convert incoming value to concrete data-type.
 */
class ValueNormalizer
{
    const DEFAULT_REQUIREMENT = '.+';

    /** @var ActionProcessorInterface */
    protected $processor;

    /** @var string[] */
    protected $requirements = [];

    /**
     * List of data types, values of such types will be cached locally.
     *
     * @var array
     */
    protected $cachedData = [
        DataType::ENTITY_TYPE  => [],
        DataType::ENTITY_CLASS => [],
    ];

    /**
     * @param ActionProcessorInterface $processor
     */
    public function __construct(ActionProcessorInterface $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Enables local cache for given data type values.
     * Values of this type should be scalar or objects that can be represented as string (by method __toString).
     *
     * @param string $dataType
     */
    public function enableCacheForDataType($dataType)
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
     *
     * @return mixed
     */
    public function normalizeValue($value, $dataType, RequestType $requestType, $isArrayAllowed = false)
    {
        if (!isset($this->cachedData[$dataType])) {
            return $this->getNormalizedValue($dataType, $requestType, $value, $isArrayAllowed);
        }

        $cacheKey = (string)$value  . '|' . (string)$requestType . '|' . ($isArrayAllowed ? '+' : '-');
        if (array_key_exists($cacheKey, $this->cachedData[$dataType])) {
            return $this->cachedData[$dataType][$cacheKey];
        }

        $result = $this->getNormalizedValue($dataType, $requestType, $value, $isArrayAllowed);

        $this->cachedData[$dataType][$cacheKey] = $result;

        return $result;
    }

    /**
     * Gets a regular expression that can be used to validate a value of the given data-type.
     *
     * @param string      $dataType       The data-type.
     * @param RequestType $requestType    The request type, for example "rest", "soap", etc.
     * @param bool        $isArrayAllowed Whether a value can be an array.
     *
     * @return string
     */
    public function getRequirement($dataType, RequestType $requestType, $isArrayAllowed = false)
    {
        $requirementKey = $dataType . '|' . (string)$requestType . ($isArrayAllowed ? '|arr' : '');
        if (!array_key_exists($requirementKey, $this->requirements)) {
            $context = $this->doNormalization($dataType, $requestType, null, $isArrayAllowed);

            $this->requirements[$requirementKey] = $context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$requirementKey];
    }

    /**
     * @param string      $dataType
     * @param RequestType $requestType
     * @param mixed|null  $value
     * @param bool        $isArrayAllowed
     *
     * @return NormalizeValueContext
     * @throws \Exception
     */
    protected function doNormalization($dataType, RequestType $requestType, $value = null, $isArrayAllowed = false)
    {
        /** @var NormalizeValueContext $context */
        $context = $this->processor->createContext();
        $context->getRequestType()->set($requestType);
        $context->setDataType($dataType);
        $context->setResult($value);
        $context->setArrayAllowed($isArrayAllowed);
        try {
            $this->processor->process($context);
        } catch (\Exception $e) {
            throw ExceptionUtil::getProcessorUnderlyingException($e);
        }

        return $context;
    }

    /**
     * @param string      $dataType
     * @param RequestType $requestType
     * @param mixed       $value
     * @param bool        $isArrayAllowed
     *
     * @return mixed
     */
    protected function getNormalizedValue($dataType, RequestType $requestType, $value, $isArrayAllowed)
    {
        return $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed)->getResult();
    }
}
