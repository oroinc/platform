<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * The base class for filters that can be used independently of other filters
 * and have a predefined default value.
 * Also this class can be used for some custom filters.
 */
class StandaloneFilterWithDefaultValue extends StandaloneFilter
{
    private mixed $defaultValue;
    /** @var callable|null */
    private mixed $defaultValueToStringConverter;

    /**
     * @param string              $dataType
     * @param string|null         $description
     * @param mixed|callable|null $defaultValue
     * @param callable|null       $defaultValueToStringConverter
     */
    public function __construct(
        string $dataType,
        string $description = null,
        mixed $defaultValue = null,
        ?callable $defaultValueToStringConverter = null
    ) {
        parent::__construct($dataType, $description);
        $this->defaultValue = $defaultValue;
        $this->defaultValueToStringConverter = $defaultValueToStringConverter;
    }

    /**
     * Checks if the filter default value is set.
     */
    public function hasDefaultValue(): bool
    {
        return null !== $this->defaultValue;
    }

    /**
     * Gets the filter default value.
     */
    public function getDefaultValue(): mixed
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
    public function setDefaultValue(mixed $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * Gets a string representation of the filter default value.
     */
    public function getDefaultValueString(): string
    {
        $value = $this->getDefaultValue();
        if (null !== $this->defaultValueToStringConverter) {
            return \call_user_func($this->defaultValueToStringConverter, $value);
        }

        return $value ?? (string)$value;
    }

    /**
     * Sets a function that should be used to convert the filter default value to a string.
     */
    public function setDefaultValueToStringConverter(?callable $converter): void
    {
        $this->defaultValueToStringConverter = $converter;
    }
}
