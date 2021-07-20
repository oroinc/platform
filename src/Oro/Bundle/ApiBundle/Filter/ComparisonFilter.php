<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;

/**
 * A filter that can be used to filter data by a field value.
 * Supported comparison types:
 * * equal to
 * * not equal to
 * * less than
 * * less than or equal to
 * * greater than
 * * greater than or equal to
 * * exists (is not null for fields and to-one associations
 *   and check whether a collection is not empty for to-many associations)
 * * not exists (is null for fields and to-one associations
 *   and check whether a collection is empty for to-many associations)
 * * not equal to or is null for fields and to-one associations,
 *   for to-many associations checks whether a collection does not contain any of specific values
 *   or a collection is empty
 * * contains for string fields and to-one associations with string identifier,
 *   for to-many associations checks whether a collection contains all of specific values
 * * not contains for string fields and to-one associations with string identifier,
 *   for to-many associations checks whether a collection does not contain all of specific values
 * * starts with
 * * not starts with
 * * ends with
 * * not ends with
 * Also the field value can be:
 * * an array, in this case IN expression will be used
 * * an instance of Range class, in this case BETWEEN expression will be used
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ComparisonFilter extends StandaloneFilter implements FieldAwareFilterInterface, CollectionAwareFilterInterface
{
    /** @var string[] The list of operators that support several values, e.g. an array or a range */
    private const SUPPORT_SEVERAL_VALUES_OPERATORS = [
        FilterOperator::EQ,
        FilterOperator::NEQ,
        FilterOperator::NEQ_OR_NULL
    ];

    /** @var string[] The list of operators that support several values specified as an array for collection */
    private const SUPPORT_ARRAY_VALUES_OPERATORS_COLLECTION = [
        FilterOperator::CONTAINS,
        FilterOperator::NOT_CONTAINS
    ];

    /** @var string */
    private $field;

    /** @var bool */
    private $collection = false;

    /** @var bool */
    private $caseInsensitive = false;

    /** @var callable|null */
    private $valueTransformer;

    /**
     * Gets a field by which the data is filtered.
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * {@inheritdoc}
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * Indicates whether the filter represents a collection valued association.
     */
    public function isCollection(): bool
    {
        return $this->collection;
    }

    /**
     * {@inheritdoc}
     */
    public function setCollection(bool $collection): void
    {
        $this->collection = $collection;
    }

    /**
     * {@inheritdoc}
     */
    public function isArrayAllowed(string $operator = null): bool
    {
        return
            parent::isArrayAllowed($operator)
            && (
                null === $operator
                || \in_array($operator, self::SUPPORT_SEVERAL_VALUES_OPERATORS, true)
                || (
                    $this->isCollection()
                    && \in_array($operator, self::SUPPORT_ARRAY_VALUES_OPERATORS_COLLECTION, true)
                )
            );
    }

    /**
     * {@inheritdoc}
     */
    public function isRangeAllowed(string $operator = null): bool
    {
        return
            parent::isRangeAllowed($operator)
            && (
                null === $operator
                || \in_array($operator, self::SUPPORT_SEVERAL_VALUES_OPERATORS, true)
            );
    }

    /**
     * Sets a value that indicates whether case-insensitive comparison should be used.
     */
    public function setCaseInsensitive(bool $caseInsensitive): void
    {
        $this->caseInsensitive = $caseInsensitive;
    }

    /**
     * Sets a callable that should be used to transform the filter value.
     *
     * @param callable $valueTransformer
     */
    public function setValueTransformer($valueTransformer): void
    {
        $this->valueTransformer = $valueTransformer;
    }

    /**
     * {@inheritdoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $expr = $this->createExpression($value);
        if (null !== $expr) {
            $criteria->andWhere($expr);
        }
    }

    /**
     * Creates an expression that can be used to in WHERE statement to filter data by this filter.
     */
    protected function createExpression(FilterValue $value = null): ?Expression
    {
        if (null === $value) {
            return null;
        }
        $field = $this->getField();
        if (!$field) {
            throw new \InvalidArgumentException('The field must not be empty.');
        }
        if (ConfigUtil::IGNORE_PROPERTY_PATH === $field) {
            return null;
        }

        return $this->buildExpression($field, $value->getPath(), $value->getOperator(), $value->getValue());
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
     * @throws InvalidFilterOperatorException
     */
    protected function buildExpression(string $field, string $path, ?string $operator, $value): Expression
    {
        if (null === $value) {
            throw new \InvalidArgumentException(\sprintf('The value must not be NULL. Field: "%s".', $field));
        }

        if (null === $operator) {
            $operator = FilterOperator::EQ;
        }
        if (\in_array($operator, $this->getSupportedOperators(), true)) {
            $expr = $this->isCollection()
                ? $this->doBuildCollectionExpression($field, $path, $operator, $value)
                : $this->doBuildExpression($field, $path, $operator, $value);
            if (null !== $expr) {
                return $expr;
            }
        }

        throw new InvalidFilterOperatorException($operator);
    }

    /**
     * @param string $field
     * @param string $path
     * @param string $operator
     * @param mixed  $value
     *
     * @return Expression|null
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function doBuildExpression(string $field, string $path, string $operator, $value): ?Expression
    {
        switch ($operator) {
            case FilterOperator::EQ:
                return $this->buildEqualToExpression($field, $value);
            case FilterOperator::NEQ:
                return $this->buildNotEqualToExpression($field, $value);
            case FilterOperator::GT:
                return $this->buildComparisonExpression($field, Comparison::GT, $value);
            case FilterOperator::LT:
                return $this->buildComparisonExpression($field, Comparison::LT, $value);
            case FilterOperator::GTE:
                return $this->buildComparisonExpression($field, Comparison::GTE, $value);
            case FilterOperator::LTE:
                return $this->buildComparisonExpression($field, Comparison::LTE, $value);
            case FilterOperator::EXISTS:
                return $this->buildComparisonExpression($field, 'EXISTS', $value);
            case FilterOperator::NEQ_OR_NULL:
                return $this->buildComparisonExpression($field, 'NEQ_OR_NULL', $value);
            case FilterOperator::CONTAINS:
                return $this->buildComparisonExpression($field, Comparison::CONTAINS, $value);
            case FilterOperator::NOT_CONTAINS:
                return $this->buildComparisonExpression($field, 'NOT_CONTAINS', $value);
            case FilterOperator::STARTS_WITH:
                return $this->buildComparisonExpression($field, Comparison::STARTS_WITH, $value);
            case FilterOperator::NOT_STARTS_WITH:
                return $this->buildComparisonExpression($field, 'NOT_STARTS_WITH', $value);
            case FilterOperator::ENDS_WITH:
                return $this->buildComparisonExpression($field, Comparison::ENDS_WITH, $value);
            case FilterOperator::NOT_ENDS_WITH:
                return $this->buildComparisonExpression($field, 'NOT_ENDS_WITH', $value);
        }

        return null;
    }

    /**
     * @param string $field
     * @param string $path
     * @param string $operator
     * @param mixed  $value
     *
     * @return Expression|null
     */
    protected function doBuildCollectionExpression(string $field, string $path, string $operator, $value): ?Expression
    {
        switch ($operator) {
            case FilterOperator::EQ:
                return $this->buildComparisonExpression($field, Comparison::MEMBER_OF, $value);
            case FilterOperator::NEQ:
                return $this->buildNotExpression(
                    $this->buildComparisonExpression($field, Comparison::MEMBER_OF, $value)
                );
            case FilterOperator::EXISTS:
                return $this->buildComparisonExpression($field, 'EMPTY', !$value);
            case FilterOperator::NEQ_OR_NULL:
                return $this->buildComparisonExpression($field, 'NEQ_OR_EMPTY', $value);
            case FilterOperator::CONTAINS:
                return $this->buildComparisonExpression($field, 'ALL_MEMBER_OF', $value);
            case FilterOperator::NOT_CONTAINS:
                return $this->buildComparisonExpression($field, 'ALL_NOT_MEMBER_OF', $value);
        }

        return null;
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return Expression
     */
    protected function buildEqualToExpression(string $field, $value): Expression
    {
        if (\is_array($value)) {
            return $this->buildComparisonExpression($field, Comparison::IN, $value);
        }
        if ($value instanceof Range) {
            // expression: (field >= fromValue AND field <= toValue)
            // this expression equals to "field BETWEEN fromValue AND toValue",
            // but Criteria object does not support BETWEEN expression
            return Criteria::expr()->andX(
                $this->buildComparisonExpression($field, Comparison::GTE, $value->getFromValue()),
                $this->buildComparisonExpression($field, Comparison::LTE, $value->getToValue())
            );
        }

        return $this->buildComparisonExpression($field, Comparison::EQ, $value);
    }

    /**
     * @param string $field
     * @param mixed  $value
     *
     * @return Expression
     */
    protected function buildNotEqualToExpression(string $field, $value): Expression
    {
        if (\is_array($value)) {
            return $this->buildComparisonExpression($field, Comparison::NIN, $value);
        }
        if ($value instanceof Range) {
            // expression: (field < fromValue OR field > toValue)
            // this expression equals to "NOT field BETWEEN fromValue AND toValue",
            // but Criteria object does not support NOT and BETWEEN expressions
            return Criteria::expr()->orX(
                $this->buildComparisonExpression($field, Comparison::LT, $value->getFromValue()),
                $this->buildComparisonExpression($field, Comparison::GT, $value->getToValue())
            );
        }

        return $this->buildComparisonExpression($field, Comparison::NEQ, $value);
    }

    /**
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     *
     * @return Comparison
     */
    protected function buildComparisonExpression(string $field, string $operator, $value): Comparison
    {
        if ($this->caseInsensitive) {
            $operator .= '/i';
            $value = $this->transformValue($value, 'strtolower');
        } elseif (null !== $this->valueTransformer) {
            $value = $this->transformValue($value, $this->valueTransformer);
        }

        return new Comparison($field, $operator, new Value($value));
    }

    protected function buildNotExpression(Expression $expr): Expression
    {
        return new CompositeExpression('NOT', [$expr]);
    }

    /**
     * @param mixed    $value
     * @param callable $transformer
     *
     * @return mixed
     */
    private function transformValue($value, $transformer)
    {
        if (null !== $value) {
            if (\is_array($value)) {
                $value = \array_map($transformer, $value);
            } else {
                $value = $transformer($value);
            }
        }

        return $value;
    }
}
