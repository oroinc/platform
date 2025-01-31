<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;

/**
 * The base class for filters that can be used to filter data by a composite identifier.
 * This filter supports only "equal" and "not equal" comparisons.
 * Also filtering by several identifiers is supported.
 */
abstract class AbstractCompositeIdentifierFilter extends StandaloneFilter implements FieldFilterInterface
{
    #[\Override]
    public function apply(Criteria $criteria, ?FilterValue $value = null): void
    {
        if (null !== $value) {
            $criteria->andWhere(
                $this->buildExpression($value->getOperator(), $value->getValue())
            );
        }
    }

    protected function buildExpression(?string $operator, mixed $value): Expression
    {
        if (null === $value) {
            throw new \InvalidArgumentException('The value must not be NULL.');
        }

        if (null === $operator) {
            $operator = FilterOperator::EQ;
        }
        if (!\in_array($operator, $this->getSupportedOperators(), true)) {
            throw new \InvalidArgumentException(sprintf(
                'The operator "%s" is not supported.',
                $operator
            ));
        }

        if ($this->isListOfIdentifiers($value)) {
            return $this->buildExpressionForListOfIdentifiers($operator, $value);
        }

        return $this->buildExpressionForSingleIdentifier($operator, $value);
    }

    protected function buildExpressionForSingleIdentifier(string $operator, mixed $value): Expression
    {
        if (FilterOperator::EQ === $operator) {
            // expression: field1 = value1 AND field2 = value2 AND ...
            return $this->buildEqualExpression($this->parseIdentifier($value));
        }
        if (FilterOperator::NEQ === $operator) {
            // expression: field1 != value1 OR field2 != value2 OR ...
            // this expression equals to NOT (field1 = value1 AND field2 = value2 AND ...),
            // but Criteria object does not support NOT expression
            return $this->buildNotEqualExpression($this->parseIdentifier($value));
        }
        throw new InvalidFilterOperatorException($operator);
    }

    protected function buildExpressionForListOfIdentifiers(string $operator, array $value): Expression
    {
        $expressions = [];
        foreach ($value as $val) {
            $expressions[] = $this->buildExpressionForSingleIdentifier($operator, $val);
        }

        if (FilterOperator::EQ === $operator) {
            // expression: (field1 = value1 AND field2 = value2 AND ...) OR (...)
            return new CompositeExpression(CompositeExpression::TYPE_OR, $expressions);
        }
        if (FilterOperator::NEQ === $operator) {
            // expression: (field1 != value1 OR field2 != value2 OR ...) AND (...)
            // this expression equals to NOT ((field1 = value1 AND field2 = value2 AND ...) OR (...)),
            // but Criteria object does not support NOT expression
            return new CompositeExpression(CompositeExpression::TYPE_AND, $expressions);
        }
        throw new InvalidFilterOperatorException($operator);
    }

    protected function isListOfIdentifiers(mixed $value): bool
    {
        return \is_array($value);
    }

    abstract protected function buildEqualExpression(array $value): Expression;

    abstract protected function buildNotEqualExpression(array $value): Expression;

    abstract protected function parseIdentifier(mixed $value): mixed;
}
