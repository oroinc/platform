<?php

namespace Oro\Bundle\ApiBundle\Filter;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterOperatorException;

/**
 * A filter that can be used to filter child nodes of a tree by a specific parent node
 * and independs on the nesting level of child nodes.
 * Supported modes:
 * * greater than (gt) - returns all child nodes for a given node
 * * greater than or equal to (gte) - returns a given node and all child nodes for this node
 * Note: this filter can be used only for entities based on the nested tree from Gedmo extensions for Doctrine.
 * @link http://atlantic18.github.io/DoctrineExtensions/doc/tree.html
 */
class NestedTreeFilter extends StandaloneFilter implements FieldFilterInterface
{
    private ?string $field = null;

    /**
     * Sets an association name by which the data is filtered.
     */
    public function setField(string $field): void
    {
        $this->field = $field;
    }

    /**
     * {@inheritDoc}
     */
    public function apply(Criteria $criteria, FilterValue $value = null): void
    {
        $expr = $this->createExpression($value);
        if (null !== $expr) {
            $criteria->andWhere($expr);
        }
    }

    private function createExpression(?FilterValue $value): ?Expression
    {
        if (null === $value) {
            return null;
        }

        $path = $value->getPath();
        if (str_contains($path, '.')) {
            throw new InvalidFilterException('This filter is not supported for associations.');
        }

        return new Comparison(
            $this->field ?? '',
            $this->getComparisonExpressionOperator($value->getOperator()),
            new Value($value->getValue())
        );
    }

    private function getComparisonExpressionOperator(?string $operator): string
    {
        if ($operator && \in_array($operator, $this->getSupportedOperators(), true)) {
            if (FilterOperator::GT === $operator) {
                return 'NESTED_TREE';
            }
            if (FilterOperator::GTE === $operator) {
                return 'NESTED_TREE_WITH_ROOT';
            }
        }

        throw new InvalidFilterOperatorException($operator ?? FilterOperator::EQ);
    }
}
