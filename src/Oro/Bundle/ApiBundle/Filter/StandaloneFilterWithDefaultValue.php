<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A base class for filters that can be used independently from other filters
 * and can have predefined default value.
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
        return is_callable($this->defaultValue)
            ? call_user_func($this->defaultValue)
            : $this->defaultValue;
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
        if (null !== $this->defaultValueToStringConverter) {
            return call_user_func($this->defaultValueToStringConverter, $this->getDefaultValue());
        }

        $value = $this->getDefaultValue();

        return null !== $value
            ? $value
            : (string)$value;
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
