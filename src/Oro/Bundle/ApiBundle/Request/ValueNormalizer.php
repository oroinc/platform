<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;
use Oro\Bundle\ApiBundle\Util\ExceptionUtil;

/**
 * Provides a way to convert incoming value to concrete data-type.
 */
class ValueNormalizer
{
    const DEFAULT_REQUIREMENT = '.+';

    /** @var NormalizeValueProcessor */
    protected $processor;

    /** @var string[] */
    protected $requirements = [];

    /**
     * List of data types, values of such types will be cached locally.
     *
     * @var array
     */
    protected $cachedDataTypes = [
        DataType::ENTITY_TYPE         => true,
        DataType::ENTITY_CLASS        => true,
        DataType::ENTITY_ALIAS        => true,
        DataType::ENTITY_PLURAL_ALIAS => true,
    ];

    /** @var array */
    private $cachedData = [];

    /**
     * @param NormalizeValueProcessor $processor
     */
    public function __construct(NormalizeValueProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Enables local cache for given data type values.
     *
     * @param string $dataType
     */
    public function enableCacheForDataType($dataType)
    {
        $this->cachedDataTypes[$dataType] = true;
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
        if (!array_key_exists($dataType, $this->cachedDataTypes)) {
            $context = $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed);

            return $context->getResult();
        }

        if (!isset($this->cachedData[$dataType][$value])) {
            $context = $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed);
            $this->cachedData[$dataType][$value] = $context->getResult();
        }

        return $this->cachedData[$dataType][$value];
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
        $context->getRequestType()->set($requestType->toArray());
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
}
