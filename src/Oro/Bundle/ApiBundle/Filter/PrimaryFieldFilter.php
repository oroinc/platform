<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;

/**
 * A filter that can be used to filter data by a value of "primary" field.
 */
class PrimaryFieldFilter extends ComparisonFilter
{
    /** @var string */
    protected $dataField;

    /** @var string */
    protected $primaryFlagField;

    /**
     * Gets a field that contains a data value.
     *
     * @return string|null
     */
    public function getDataField()
    {
        return $this->dataField;
    }

    /**
     * Sets a field that contains a data value.
     *
     * @param string $fieldName
     */
    public function setDataField($fieldName)
    {
        $this->dataField = $fieldName;
    }

    /**
     * Gets a field that contains a "primary" flag.
     *
     * @return string|null
     */
    public function getPrimaryFlagField()
    {
        return $this->primaryFlagField;
    }

    /**
     * Sets a field that contains a "primary" flag.
     *
     * @param string $fieldName
     */
    public function setPrimaryFlagField($fieldName)
    {
        $this->primaryFlagField = $fieldName;
    }

    /**
     * {@inheritdoc}
     */
    protected function createExpression(FilterValue $value = null)
    {
        if (null === $value) {
            return null;
        }

        if (!$this->field) {
            throw new \InvalidArgumentException('The Field must not be empty.');
        }
        if (!$this->dataField) {
            throw new \InvalidArgumentException('The DataField must not be empty.');
        }

        $expr = $this->buildExpression(
            $this->field . '.' . $this->dataField,
            $value->getPath(),
            $value->getOperator(),
            $value->getValue()
        );

        return Criteria::expr()->andX(
            $expr,
            Criteria::expr()->eq($this->field . '.' . ($this->primaryFlagField  ?: 'primary'), true)
        );
    }
}
