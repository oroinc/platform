<?php

namespace Oro\Bundle\ApiBundle\Collection\QueryVisitorExpression;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ApiBundle\Collection\QueryExpressionVisitor;
use Oro\Component\DoctrineUtils\ORM\QueryBuilderUtil;

/**
 * Represents ENTITY comparison expression.
 * This expression checks whether a field contains an identifier of an entity matched specified criteria.
 * Example:
 *   e.entityId IN (SELECT targetEntity.id FROM Target\Entity WHERE targetEntity.name = 'name you are looking for')
 * Example usage in filters:
 *   $this->buildComparisonExpression('entityId', 'ENTITY', [
 *       'Target\Entity',
 *       $this->buildEqualToExpression('name', 'name you are looking for')
 *   ])
 */
class EntityComparisonExpression implements ComparisonExpressionInterface
{
    public function __construct(
        private readonly ManagerRegistry $doctrine
    ) {
    }
    #[\Override]
    public function walkComparisonExpression(
        QueryExpressionVisitor $visitor,
        string $field,
        string $expression,
        string $parameterName,
        mixed $value
    ): mixed {
        $subquery = $visitor->createQuery($value[0]);
        $subqueryAlias = QueryBuilderUtil::getSingleRootAlias($subquery);
        $subquery->select($subqueryAlias . '.' . $this->getIdFieldName($value[0]));

        $exprVisitor = new EntityComparisonExpressionVisitor($subqueryAlias, $parameterName);
        $subquery->andWhere($exprVisitor->dispatch($value[1]));
        foreach ($exprVisitor->getParameters() as $parameter) {
            $visitor->addParameter($parameter);
        }

        return $visitor->getExpressionBuilder()->in($field, $subquery->getDQL());
    }

    public function getIdFieldName(string $entityClass): string
    {
        $idFieldNames = $this->doctrine->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();
        if (\count($idFieldNames) !== 1) {
            throw new \LogicException(\sprintf(
                'The "%s" entity must have a single field identifier.',
                $entityClass
            ));
        }

        return reset($idFieldNames);
    }
}
