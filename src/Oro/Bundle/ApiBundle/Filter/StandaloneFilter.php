<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * A base class for filters that can be used independently from other filters.
 */
abstract class StandaloneFilter implements FilterInterface
{
    const EQ = '=';

    /** @var string */
    protected $dataType;

    /** @var bool */
    protected $arrayAllowed = false;

    /** @var string */
    protected $description;

    /** @var string[] */
    protected $operators;

    /**
     * @param string      $dataType
     * @param string|null $description
     */
    public function __construct($dataType, $description = null)
    {
        $this->dataType = $dataType;
        $this->description = $description;
        $this->operators = [self::EQ];
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
     * Gets a flag determines if a value can be an array.
     *
     * @param string|null $operator
     *
     * @return bool
     */
    public function isArrayAllowed($operator = null)
    {
        return $this->arrayAllowed;
    }

    /**
     * Sets a flag determines if a value can be an array.
     *
     * @param bool $arrayAllowed
     */
    public function setArrayAllowed($arrayAllowed)
    {
        $this->arrayAllowed = $arrayAllowed;
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
     * Gets a list of operators supported by this filter.
     *
     * @return string[] The list of operators
     */
    public function getSupportedOperators()
    {
        return $this->operators;
    }

    /**
     * Sets a list of operators supported by this filter.
     *
     * @param string[] $operators
     */
    public function setSupportedOperators(array $operators)
    {
        $this->operators = $operators;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function createExpression(FilterValue $value = null)
    {
        return null;
    }
}
