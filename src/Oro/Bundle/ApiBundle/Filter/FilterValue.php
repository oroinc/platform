<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents input value that is used to filter data requested by Data API.
 */
final class FilterValue
{
    /** @var string */
    private $path;

    /** @var mixed */
    private $value;

    /** @var string|null */
    private $operator;

    /** @var string|null */
    private $sourceKey;

    /** @var string|null */
    private $sourceValue;

    /**
     * @param string      $path
     * @param mixed       $value
     * @param string|null $operator
     */
    public function __construct(string $path, $value, string $operator = null)
    {
        $this->path = $path;
        $this->value = $value;
        $this->operator = $operator;
    }

    /**
     * @param string      $sourceKey
     * @param string      $path
     * @param mixed       $value
     * @param string|null $operator
     *
     * @return FilterValue
     */
    public static function createFromSource(
        string $sourceKey,
        string $path,
        string $value,
        string $operator = null
    ): FilterValue {
        $filterValue = new FilterValue($path, $value, $operator);
        $filterValue->sourceKey = $sourceKey;
        $filterValue->sourceValue = $value;

        return $filterValue;
    }

    /**
     * @param FilterValue $source
     */
    public function setSource(FilterValue $source): void
    {
        $this->sourceKey = $source->sourceKey;
        $this->sourceValue = $source->sourceValue;
    }

    /**
     * Gets a path the filter is applied.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets a path the filter is applied.
     *
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Gets a value of a filter.
     *
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Sets a value of a filter.
     *
     * @param mixed $value
     */
    public function setValue($value): void
    {
        $this->value = $value;
    }

    /**
     * Gets an operator of a filter.
     *
     * @return string|null
     */
    public function getOperator(): ?string
    {
        return $this->operator;
    }

    /**
     * Sets an operator of a filter.
     *
     * @param string|null $operator
     */
    public function setOperator(?string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * Gets a key this value was come from a request.
     * E.g. it can be URI query parameter for REST API filters.
     *
     * @return string|null
     */
    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    /**
     * Gets a value was come from a request.
     * E.g. it can be URI query parameter value for REST API filters.
     *
     * @return string|null
     */
    public function getSourceValue(): ?string
    {
        return $this->sourceValue;
    }
}
