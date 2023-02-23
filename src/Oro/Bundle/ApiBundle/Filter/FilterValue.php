<?php

namespace Oro\Bundle\ApiBundle\Filter;

/**
 * Represents input value that is used to filter data requested by API.
 */
final class FilterValue
{
    private string $path;
    private mixed $value;
    private ?string $operator;
    private ?string $sourceKey = null;
    private ?string $sourceValue = null;

    public function __construct(string $path, mixed $value, ?string $operator = null)
    {
        $this->path = $path;
        $this->value = $value;
        $this->operator = $operator;
    }

    public static function createFromSource(
        string $sourceKey,
        string $path,
        string $value,
        string $operator = null
    ): self {
        $filterValue = new FilterValue($path, $value, $operator);
        $filterValue->sourceKey = $sourceKey;
        $filterValue->sourceValue = $value;

        return $filterValue;
    }

    public function setSource(FilterValue $source): void
    {
        $this->sourceKey = $source->sourceKey;
        $this->sourceValue = $source->sourceValue;
    }

    /**
     * Gets a path the filter is applied.
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets a path the filter is applied.
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    /**
     * Gets a value of a filter.
     */
    public function getValue(): mixed
    {
        return $this->value;
    }

    /**
     * Sets a value of a filter.
     */
    public function setValue(mixed $value): void
    {
        $this->value = $value;
    }

    /**
     * Gets an operator of a filter.
     */
    public function getOperator(): ?string
    {
        return $this->operator;
    }

    /**
     * Sets an operator of a filter.
     */
    public function setOperator(?string $operator): void
    {
        $this->operator = $operator;
    }

    /**
     * Gets a key this value was come from a request.
     * E.g. it can be URI query parameter for REST API filters.
     */
    public function getSourceKey(): ?string
    {
        return $this->sourceKey;
    }

    /**
     * Gets a value was come from a request.
     * E.g. it can be URI query parameter value for REST API filters.
     */
    public function getSourceValue(): ?string
    {
        return $this->sourceValue;
    }
}
