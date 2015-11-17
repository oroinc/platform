<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;
use Oro\Bundle\ApiBundle\Util\ExceptionHelper;

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
     * @param string      $requestType    The type of API request, for example "rest", "soap", "odata", etc.
     * @param string|null $arrayDelimiter If specified a value can be an array.
     *
     * @return mixed
     */
    public function normalizeValue($value, $dataType, $requestType, $arrayDelimiter = null)
    {
        $context = $this->doNormalization($dataType, $requestType, $value, $arrayDelimiter);

        return $context->getResult();
    }

    /**
     * Gets a regular expression that can be used to validate a value of the given data-type.
     *
     * @param string      $dataType       The data-type.
     * @param string      $requestType    The type of API request, for example "rest", "soap", "odata", etc.
     * @param string|null $arrayDelimiter If specified a value can be an array.
     *
     * @return string
     */
    public function getRequirement($dataType, $requestType, $arrayDelimiter = null)
    {
        $requirementKey = $dataType . $requestType . (!empty($arrayDelimiter) ? $arrayDelimiter : '');
        if (!array_key_exists($requirementKey, $this->requirements)) {
            $context = $this->doNormalization($dataType, $requestType, null, $arrayDelimiter);

            $this->requirements[$requirementKey] = $context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$requirementKey];
    }

    /**
     * @param string      $dataType
     * @param string      $requestType
     * @param mixed|null  $value
     * @param string|null $arrayDelimiter
     *
     * @return NormalizeValueContext
     */
    protected function doNormalization($dataType, $requestType, $value = null, $arrayDelimiter = null)
    {
        /** @var NormalizeValueContext $context */
        $context = $this->processor->createContext();
        $context->setRequestType($requestType);
        $context->setDataType($dataType);
        $context->setResult($value);
        $context->removeRequirement();

        if (!empty($arrayDelimiter)) {
            $context->setArrayAllowed(true);
            $context->setArrayDelimiter($arrayDelimiter);
        }
        try {
            $this->processor->process($context);
        } catch (\Exception $e) {
            throw ExceptionHelper::getProcessorUnderlyingException($e);
        }

        return $context;
    }
}
