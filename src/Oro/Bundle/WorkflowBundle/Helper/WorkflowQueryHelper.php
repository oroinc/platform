<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

class WorkflowQueryHelper
{
    /**
     * @param QueryBuilder $queryBuilder
     * @param string $stepAlias default 'workflowStep'
     * @param string $itemAlias default 'workflowItem'
     * @return QueryBuilder
     */
    public static function addQuery(
        QueryBuilder $queryBuilder,
        $stepAlias = 'workflowStep',
        $itemAlias = 'workflowItem'
    ) {
        list($entityClass) = $queryBuilder->getRootEntities();
        list($entityIdentifier) = $queryBuilder->getEntityManager()->getClassMetadata($entityClass)
            ->getIdentifierFieldNames();

        list($rootAlias) = $queryBuilder->getRootAliases();

        $queryBuilder
            ->leftJoin(
                WorkflowItem::class,
                $itemAlias,
                Join::WITH,
                self::getItemCondition($rootAlias, $entityClass, $entityIdentifier, $itemAlias)
            )
            ->leftJoin(sprintf('%s.currentStep', $itemAlias), $stepAlias);

        return $queryBuilder;
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
    public static function addDatagridQuery(
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
            'condition' => self::getItemCondition($entityAlias, $entityClass, $entityIdentifier, $itemAlias),
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
    protected static function getItemCondition($entityAlias, $entityClass, $entityIdentifier, $itemAlias)
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
