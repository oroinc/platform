<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * The base class for filters that can be used independently of other filters.
 * Also this class can be used for some custom filters which cannot have a default value.
 */
class StandaloneFilter implements FilterInterface
{
    /** @var string */
    private $dataType;

    /** @var bool */
    private $arrayAllowed = false;

    /** @var bool */
    private $rangeAllowed = false;

    /** @var string|null */
    private $description;

    /** @var string[] */
    private $operators;

    /**
     * @param string      $dataType
     * @param string|null $description
     */
    public function __construct(string $dataType, string $description = null)
    {
        $this->dataType = $dataType;
        $this->description = $description;
        $this->operators = [FilterOperator::EQ];
    }

    /**
     * Gets a data-type of a value the filter works with.
     *
     * @return string
     */
    public function getDataType(): string
    {
        return $this->dataType;
    }

    /**
     * Sets a data-type of a value the filter works with.
     *
     * @param string $dataType
     */
    public function setDataType(string $dataType): void
    {
        $this->dataType = $dataType;
    }

    /**
     * Gets a flag determines if a value can be an array.
     *
     * @param string|null $operator
     *
     * @return bool
     */
    public function isArrayAllowed(string $operator = null): bool
    {
        return $this->arrayAllowed;
    }

    /**
     * Sets a flag determines if a value can be an array.
     *
     * @param bool $arrayAllowed
     */
    public function setArrayAllowed(bool $arrayAllowed): void
    {
        $this->arrayAllowed = $arrayAllowed;
    }

    /**
     * Gets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @param string|null $operator
     *
     * @return bool
     */
    public function isRangeAllowed(string $operator = null): bool
    {
        return $this->rangeAllowed;
    }

    /**
     * Sets a flag determines if a value can be a pair of "from" and "to" values.
     *
     * @param bool $rangeAllowed
     */
    public function setRangeAllowed(bool $rangeAllowed): void
    {
        $this->rangeAllowed = $rangeAllowed;
    }

    /**
     * Gets the filter description.
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Sets the filter description.
     *
     * @param string|null $description
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
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
    }
}
