<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\Query\QueryException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Gedmo\Tree\TreeListener;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Bundle\ApiBundle\Model\Range;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents NESTED_TREE and NESTED_TREE_WITH_ROOT comparison expression.
 * The NESTED_TREE expression returns all child nodes for a given node, independs on the nesting level.
 * The NESTED_TREE_WITH_ROOT expression returns a given node and all child nodes for this node,
 * independs on the nesting level.
 * @see \Gedmo\Tree\Entity\Repository\NestedTreeRepository::childrenQueryBuilder
 */
class NestedTreeComparisonExpression implements ComparisonExpressionInterface
{
    private TreeListener $treeListener;
    private ManagerRegistry $doctrine;
    private bool $includeRoot;

    public function __construct(
        TreeListener $treeListener,
        ManagerRegistry $doctrine,
        bool $includeRoot = false
    ) {
        $this->treeListener = $treeListener;
        $this->doctrine = $doctrine;
        $this->includeRoot = $includeRoot;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        if (null === $value) {
            // the filter like NESTED_TREE for NULL does not have a sense
            throw new QueryException(sprintf('The value for "%s" must not be NULL.', $field));
        }
        if ($value instanceof Range) {
            throw new QueryException(sprintf('The value for "%s" must not be a range.', $field));
        }

        $visitor->addParameter($parameterName, $value);
        $subquery = $visitor->createSubquery($field ?: null);
        $this->buildSubquery($subquery, $visitor->buildPlaceholder($parameterName));

        return $visitor->getExpressionBuilder()->exists($subquery->getDQL());
    }

    private function buildSubquery(QueryBuilder $subquery, string $parameterPlaceholder): void
    {
        $entityClass = QueryBuilderUtil::getSingleRootEntity($subquery);
        $subqueryAlias = QueryBuilderUtil::getSingleRootAlias($subquery);
        $criteriaAlias = $subqueryAlias . '_criteria';

        $subquery->innerJoin(
            $entityClass,
            $criteriaAlias,
            Expr\Join::WITH,
            $subquery->expr()->eq($criteriaAlias, $parameterPlaceholder)
        );

        $config = $this->treeListener->getConfiguration(
            $this->doctrine->getManagerForClass($entityClass),
            $entityClass
        );
        $rightFieldName = $config['right'];
        $leftFieldName = $config['left'];

        $expressions = [
            $subquery->expr()->lt(
                $subqueryAlias . '.' . $rightFieldName,
                $criteriaAlias . '.' . $rightFieldName
            ),
            $subquery->expr()->gt(
                $subqueryAlias . '.' . $leftFieldName,
                $criteriaAlias . '.' . $leftFieldName
            )
        ];
        if (isset($config['root'])) {
            $rootFieldName = $config['root'];
            $expressions[] = $subquery->expr()->eq(
                $subqueryAlias . '.' . $rootFieldName,
                $criteriaAlias . '.' . $rootFieldName
            );
        }
        $whereExpression = new Expr\Andx($expressions);

        if ($this->includeRoot) {
            $whereExpression = $subquery->expr()->orX(
                $whereExpression,
                $subquery->expr()->eq($subqueryAlias, $parameterPlaceholder)
            );
        }

        $subquery->andWhere($whereExpression);
    }
}
