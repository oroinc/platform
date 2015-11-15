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

    /** @var NormalizeValueContext|null */
    protected $context;

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
        $this->doNormalization($dataType, $requestType, $value, $arrayDelimiter);

        return $this->context->getResult();
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
            $this->doNormalization($dataType, $requestType, null, $arrayDelimiter);
            $this->requirements[$requirementKey] = $this->context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$requirementKey];
    }

    /**
     * @param string      $dataType
     * @param string      $requestType
     * @param mixed|null  $value
     * @param string|null $arrayDelimiter
     */
    protected function doNormalization($dataType, $requestType, $value = null, $arrayDelimiter = null)
    {
        if (null === $this->context) {
            $this->context = $this->processor->createContext();
        }
        $this->context->setRequestType($requestType);
        $this->context->setDataType($dataType);
        $this->context->setResult($value);
        $this->context->removeRequirement();

        $previousArrayAllowed   = $this->context->isArrayAllowed();
        $previousArrayDelimiter = $this->context->getArrayDelimiter();
        if (!empty($arrayDelimiter)) {
            $this->context->setArrayAllowed(true);
            $this->context->setArrayDelimiter($arrayDelimiter);
        } else {
            $this->context->setArrayAllowed(false);
            $this->context->setArrayDelimiter(null);
        }
        try {
            $this->processor->process($this->context);
            $this->context->setArrayAllowed($previousArrayAllowed);
            $this->context->setArrayDelimiter($previousArrayDelimiter);
        } catch (\Exception $e) {
            $this->context->setArrayAllowed($previousArrayAllowed);
            $this->context->setArrayDelimiter($previousArrayDelimiter);

            throw ExceptionHelper::getProcessorUnderlyingException($e);
        }
    }
}
