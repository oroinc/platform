<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * A base class for filters that can be used independently from other filters.
 */
abstract class StandaloneFilter implements FilterInterface
{
    /** @var string */
    protected $dataType;

    /** @var string */
    protected $description;

    /** @var mixed|null */
    protected $defaultValue;

    /** @var callable|null */
    protected $defaultValueToStringConverter;

    /**
     * @param string              $dataType
     * @param string              $description
     * @param mixed|callable|null $defaultValue
     * @param callable|null       $defaultValueToStringConverter
     */
    public function __construct(
        $dataType,
        $description,
        $defaultValue = null,
        $defaultValueToStringConverter = null
    ) {
        $this->dataType                      = $dataType;
        $this->description                   = $description;
        $this->defaultValue                  = $defaultValue;
        $this->defaultValueToStringConverter = $defaultValueToStringConverter;
    }

    /**
     * Gets a data-type of a value the filter works with.
     *
     * @return string
     */
    public function getDataType()
    {
        return $this->dataType;
    }

    /**
     * Sets a data-type of a value the filter works with.
     *
     * @param string $dataType
     */
    public function setDataType($dataType)
    {
        $this->dataType = $dataType;
    }

    /**
     * Gets the filter description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets the filter description.
     *
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
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
