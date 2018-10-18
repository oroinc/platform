<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * The base class for filters that can be used independently of other filters
 * and have a predefined default value.
 * Also this class can be used for some custom filters.
 */
class StandaloneFilterWithDefaultValue extends StandaloneFilter
{
    /** @var mixed|null */
    protected $defaultValue;

    /** @var callable|null */
    protected $defaultValueToStringConverter;

    /**
     * @param string              $dataType
     * @param string|null         $description
     * @param mixed|callable|null $defaultValue
     * @param callable|null       $defaultValueToStringConverter
     */
    public function __construct(
        $dataType,
        $description = null,
        $defaultValue = null,
        $defaultValueToStringConverter = null
    ) {
        parent::__construct($dataType, $description);
        $this->defaultValue = $defaultValue;
        $this->defaultValueToStringConverter = $defaultValueToStringConverter;
    }

    /**
     * Checks if the filter default value is set.
     *
     * @return bool
     */
    public function hasDefaultValue()
    {
        return null !== $this->defaultValue;
    }

    /**
     * Gets the filter default value.
     *
     * @return mixed|null
     */
    public function getDefaultValue()
    {
        if (\is_callable($this->defaultValue)) {
            return \call_user_func($this->defaultValue);
        }

        return $this->defaultValue;
    }

    /**
     * Sets the filter default value.
     *
     * @param mixed|callable|null $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets a string representation of the filter default value.
     *
     * @return string
     */
    public function getDefaultValueString()
    {
        $value = $this->getDefaultValue();
        if (null !== $this->defaultValueToStringConverter) {
            return \call_user_func($this->defaultValueToStringConverter, $value);
        }

        return $value ?? (string)$value;
    }

    /**
     * Sets a function that should be used to convert the filter default value to a string.
     *
     * @param callable|null $converter
     */
    public function setDefaultValueToStringConverter($converter)
    {
        $this->defaultValueToStringConverter = $converter;
    }
}
