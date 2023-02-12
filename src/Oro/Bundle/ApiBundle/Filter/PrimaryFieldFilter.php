<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * A filter that can be used to filter data by a value of "primary" field.
 * For example this filter can be used to filter data by a primary email,
 * which value is computed based on a collection of emails where each element of this collection
 * has a "primary" boolean property indicates whether an email is a primary one or not.
 * In this case, this filter will work only with emails marked as "primary".
 * Example of usage:
 * <code>
 *  filters:
 *      fields:
 *          primaryEmail:
 *              data_type: string
 *              property_path: emails
 *              type: primaryField
 *              options:
 *                  data_field: email
 * </code>
 * @see \Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\ComputePrimaryField
 */
class PrimaryFieldFilter extends ComparisonFilter
{
    private ?string $dataField = null;
    private ?string $primaryFlagField = null;

    /**
     * Gets a field that contains a data value.
     */
    public function getDataField(): ?string
    {
        return $this->dataField;
    }

    /**
     * Sets a field that contains a data value.
     */
    public function setDataField(?string $fieldName): void
    {
        $this->dataField = $fieldName;
    }

    /**
     * Gets a field that contains a "primary" flag.
     */
    public function getPrimaryFlagField(): ?string
    {
        return $this->primaryFlagField;
    }

    /**
     * Sets a field that contains a "primary" flag.
     */
    public function setPrimaryFlagField(?string $fieldName): void
    {
        $this->primaryFlagField = $fieldName;
    }

    /**
     * {@inheritDoc}
     */
    protected function createExpression(FilterValue $value = null): ?Expression
    {
        if (null === $value) {
            return null;
        }

        $field = $this->getField();
        if (!$field) {
            throw new \InvalidArgumentException('The Field must not be empty.');
        }
        if (!$this->dataField) {
            throw new \InvalidArgumentException('The DataField must not be empty.');
        }

        $expr = $this->buildExpression(
            $field . '.' . $this->dataField,
            $value->getPath(),
            $value->getOperator(),
            $value->getValue()
        );

        return Criteria::expr()->andX(
            $expr,
            Criteria::expr()->eq($field . '.' . ($this->primaryFlagField  ?: 'primary'), true)
        );
    }
}
