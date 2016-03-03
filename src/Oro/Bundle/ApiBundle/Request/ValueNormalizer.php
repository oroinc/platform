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
     * @param NormalizeValueProcessor $processor
     */
    public function __construct(NormalizeValueProcessor $processor)
    {
        $this->processor = $processor;
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
        $context = $this->doNormalization($dataType, $requestType, $value, $isArrayAllowed);

        return $context->getResult();
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
        $context->setRequestType($requestType);
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
