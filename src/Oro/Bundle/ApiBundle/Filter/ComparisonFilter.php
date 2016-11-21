<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Expression;

/**
 * A filter that can be used to filter data by a field value.
 * Also this filter supports different kind of comparison:
 * "equal", "not equal", "less than", "less than or equal", "greater than", "greater than or equal".
 */
class ComparisonFilter extends StandaloneFilter implements FieldAwareFilterInterface
{
    const NEQ = '!=';
    const LT  = '<';
    const LTE = '<=';
    const GT  = '>';
    const GTE = '>=';

    /** @var string */
    protected $field;

    /**
     * Gets a field by which the data is filtered.
     *
     * @return string|null
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * {@inheritdoc}
     */
    public function isArrayAllowed($operator = null)
    {
        return
            parent::isArrayAllowed($operator)
            && (null === $operator || in_array($operator, [self::EQ, self::NEQ], true));
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null)
    {
        $expr = $this->createExpression($value);
        if (null !== $expr) {
            $criteria->andWhere($expr);
        }
    }

    /**
     * Creates an expression that can be used to in WHERE statement to filter data by this filter.
     *
     * @param FilterValue|null $value
     *
     * @return Expression|null
     */
    protected function createExpression(FilterValue $value = null)
    {
        return null !== $value
            ? $this->buildExpression($this->field, $value->getPath(), $value->getOperator(), $value->getValue())
            : null;
    }

    /**
     * Creates the Expression object that can be used to filter data using the Criteria object.
     *
     * @param string      $field
     * @param string      $path
     * @param string|null $operator
     * @param mixed       $value
     *
     * @return Expression
     *
     * @throws \InvalidArgumentException
     */
    protected function buildExpression($field, $path, $operator, $value)
    {
        if (!$field) {
            throw new \InvalidArgumentException('The Field must not be empty.');
        }
        if (null === $value) {
            throw new \InvalidArgumentException(
                sprintf('Value must not be NULL. Field: "%s".', $field)
            );
        }

        if (null === $operator) {
            $operator = self::EQ;
        }
        if (in_array($operator, $this->operators, true)) {
            $expr = $this->doBuildExpression($field, $path, $operator, $value);
            if (null !== $expr) {
                return $expr;
            }
        }

        throw new \InvalidArgumentException(
            sprintf('Unsupported operator: "%s". Field: "%s".', $operator, $field)
        );
    }

    /**
     * @param string $field
     * @param string $path
     * @param string $operator
     * @param mixed  $value
     *
     * @return Expression|null
     */
    protected function doBuildExpression($field, $path, $operator, $value)
    {
        switch ($operator) {
            case self::EQ:
                return is_array($value)
                    ? Criteria::expr()->in($field, $value)
                    : Criteria::expr()->eq($field, $value);
            case self::NEQ:
                return is_array($value)
                    ? Criteria::expr()->notIn($field, $value)
                    : Criteria::expr()->neq($field, $value);
            case self::GT:
                return Criteria::expr()->gt($field, $value);
            case self::LT:
                return Criteria::expr()->lt($field, $value);
            case self::GTE:
                return Criteria::expr()->gte($field, $value);
            case self::LTE:
                return Criteria::expr()->lte($field, $value);
            default:
                return null;
        }
    }
}
