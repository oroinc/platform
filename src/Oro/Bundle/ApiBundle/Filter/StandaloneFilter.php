<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * The base class for filters that can be used independently of other filters.
 * Also this class can be used for some custom filters which cannot have a default value.
 */
class StandaloneFilter implements FilterInterface
{
    private string $dataType;
    private ?string $description;
    /** @var string[] */
    private array $operators;
    private bool $arrayAllowed = false;
    private bool $rangeAllowed = false;

    public function __construct(string $dataType, string $description = null)
    {
        $this->dataType = $dataType;
        $this->description = $description;
        $this->operators = [FilterOperator::EQ];
    }

    /**
     * Gets a data-type of a value the filter works with.
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * Sets a data-type of a value the filter works with.
     */
    public function setDataType(string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * Gets a flag determines if a value can be an array.
     */
    public function isArrayAllowed(string $operator = null): bool
    {
        return $this->arrayAllowed;
    }

    /**
     * Sets a flag determines if a value can be an array.
     */
    public function setArrayAllowed(bool $arrayAllowed): void
    {
        $this->arrayAllowed = $arrayAllowed;
    }

    /**
     * Gets a flag determines if a value can be a pair of "from" and "to" values.
     */
    public function isRangeAllowed(string $operator = null): bool
    {
        return $this->rangeAllowed;
    }

    /**
     * Sets a flag determines if a value can be a pair of "from" and "to" values.
     */
    public function setRangeAllowed(bool $rangeAllowed): void
    {
        $this->rangeAllowed = $rangeAllowed;
    }

    /**
     * Gets the filter description.
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the filter description.
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * Gets a list of operators supported by this filter.
     *
     * @return string[] The list of operators
     */
    public function getSupportedOperators(): array
    {
        return $this->operators;
    }

    /**
     * Sets a list of operators supported by this filter.
     *
     * @param string[] $operators
     */
    public function setSupportedOperators(array $operators): void
    {
        $this->operators = $operators;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
    }
}
