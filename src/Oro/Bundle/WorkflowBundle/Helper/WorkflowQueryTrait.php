<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

trait WorkflowQueryTrait
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $workflowItemAlias
     * @return QueryBuilder
     */
    public function joinWorkflowItem(QueryBuilder $queryBuilder, $workflowItemAlias = 'workflowItem')
    {
        list($entityClass) = $queryBuilder->getRootEntities();
        list($entityIdentifier) = $queryBuilder->getEntityManager()->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();

        list($rootAlias) = $queryBuilder->getRootAliases();

        $queryBuilder->leftJoin(
            WorkflowItem::class,
            $workflowItemAlias,
            Join::WITH,
            $this->getItemCondition($rootAlias, $entityClass, $entityIdentifier, $workflowItemAlias)
        );

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param string $workflowStepAlias
     * @param string $workflowItemAlias workflow item alias to join through
     * @return QueryBuilder
     */
    public function joinWorkflowStep(
        QueryBuilder $queryBuilder,
        $workflowStepAlias = 'workflowStep',
        $workflowItemAlias = 'workflowItem'
    ) {
        $aliases = $queryBuilder->getAllAliases();

        if (!in_array($workflowItemAlias, $aliases, true)) {
            $this->joinWorkflowItem($queryBuilder, $workflowItemAlias);
        }

        return $queryBuilder->leftJoin(sprintf('%s.currentStep', $workflowItemAlias), $workflowStepAlias);
    }

    /**
     * @param array $query
     * @param string $entityAlias
     * @param string $entityClass
     * @param mixed $entityIdentifier
     * @param string $stepAlias default 'workflowStep'
     * @param string $itemAlias default 'workflowItem'
     * @return array
     */
    public function addDatagridQuery(
        array $query,
        $entityAlias,
        $entityClass,
        $entityIdentifier,
        $stepAlias = 'workflowStep',
        $itemAlias = 'workflowItem'
    ) {
        $query['join']['left'][] = [
            'join' => WorkflowItem::class,
            'alias' => $itemAlias,
            'conditionType' => Join::WITH,
            'condition' => $this->getItemCondition($entityAlias, $entityClass, $entityIdentifier, $itemAlias),
        ];

        $query['join']['left'][] = [
            'join' => sprintf('%s.currentStep', $itemAlias),
            'alias' => $stepAlias
        ];

        return $query;
    }

    /**
     * @param string $entityAlias
     * @param string $entityClass
     * @param string $entityIdentifier
     * @param string $itemAlias
     * @return string
     */
    protected function getItemCondition($entityAlias, $entityClass, $entityIdentifier, $itemAlias)
    {
        return sprintf(
            'CAST(%s.%s as string) = CAST(%s.entityId as string) AND %s.entityClass = \'%s\'',
            $entityAlias,
            $entityIdentifier,
            $itemAlias,
            $itemAlias,
            $entityClass
        );
    }
}
