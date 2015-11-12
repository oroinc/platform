<?php

namespace Oro\Bundle\ApiBundle\Request;

use Oro\Bundle\ApiBundle\Processor\NormalizeValue\NormalizeValueContext;
use Oro\Bundle\ApiBundle\Processor\NormalizeValueProcessor;

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
     * @param mixed  $value       A value to be converted
     * @param string $dataType    The data-type
     * @param string $requestType The type of API request, for example "rest", "soap", "odata", etc.
     *
     * @return mixed
     */
    public function normalizeValue($value, $dataType, $requestType)
    {
        $this->doNormalization($dataType, $requestType, $value);

        return $this->context->getResult();
    }

    /**
     * Gets a regular expression that can be used to validate a value of the given data-type.
     *
     * @param string $dataType    The data-type
     * @param string $requestType The type of API request, for example "rest", "soap", "odata", etc.
     *
     * @return string
     */
    public function getRequirement($dataType, $requestType)
    {
        $requirementKey = $dataType . $requestType;
        if (!array_key_exists($requirementKey, $this->requirements)) {
            $this->doNormalization($dataType, $requestType);
            $this->requirements[$requirementKey] = $this->context->getRequirement() ?: self::DEFAULT_REQUIREMENT;
        }

        return $this->requirements[$requirementKey];
    }

    /**
     * @param string $dataType
     * @param string $requestType
     * @param mixed  $value
     */
    protected function doNormalization($dataType, $requestType, $value = null)
    {
        if (null === $this->context) {
            $this->context = $this->processor->createContext();
            $this->context->setAction('normalize_value');
        }
        $this->context->setRequestType($requestType);
        $this->context->setDataType($dataType);
        $this->context->setResult($value);
        $this->context->removeRequirement();

        $this->processor->process($this->context);
    }
}
