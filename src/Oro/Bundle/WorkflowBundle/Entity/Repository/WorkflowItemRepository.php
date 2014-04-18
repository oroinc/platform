<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;

class WorkflowItemRepository extends EntityRepository
{
    const DELETE_BATCH_SIZE = 200;

    /**
     * Get workflow item associated with entity.
     *
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return WorkflowItem|null
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier);
        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return QueryBuilder
     */
    protected function getWorkflowQueryBuilder($entityClass, $entityIdentifier)
    {
        $qb = $this->createQueryBuilder('wi')
            ->innerJoin('wi.definition', 'wd')
            ->where('wd.relatedEntity = :entityClass')
            ->andWhere('wi.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifier);

        return $qb;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return QueryBuilder
     */
    public function getByDefinitionQueryBuilder(WorkflowDefinition $definition)
    {
        return $this->createQueryBuilder('workflowItem')
            ->select('workflowItem.id')
            ->where('workflowItem.definition = :definition')
            ->setParameter('definition', $definition);
    }

    /**
     * @param WorkflowDefinition $definition
     * @return QueryBuilder
     */
    public function getEntityWorkflowStepUpgradeQueryBuilder(WorkflowDefinition $definition)
    {
        $queryBuilder = $this->getByDefinitionQueryBuilder($definition);

        return $this->getEntityManager()->createQueryBuilder()
            ->update($definition->getRelatedEntity(), 'entity')
            ->set('entity.workflowStep', $definition->getStartStep()->getId())
            ->where('entity.workflowStep IS NULL')
            ->andWhere('entity.workflowItem IS NULL OR entity.workflowItem IN (' . $queryBuilder->getDQL() . ')')
            ->setParameters($queryBuilder->getParameters());
    }

    /**
     * @param string $entityName
     * @param array $excludedWorkflowNames
     * @throws \Exception
     */
    public function resetWorkflowData($entityName, $excludedWorkflowNames = array())
    {
        $entityManager = $this->getEntityManager();

        // select entities for reset
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('workflowItem.id')
            ->from($entityName, 'entity')
            ->innerJoin('entity.workflowItem', 'workflowItem')
            ->innerJoin('workflowItem.definition', 'workflowDefinition')
            ->orderBy('workflowItem.id');

        if ($excludedWorkflowNames) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('workflowDefinition.name', $excludedWorkflowNames));
        }

        $iterator = new DeletionQueryResultIterator($queryBuilder);
        $iterator->setBufferSize(self::DELETE_BATCH_SIZE);

        // wrap all operation into transaction
        $entityManager->beginTransaction();
        try {
            // iterate over workflow items
            $workflowItemIds = array();
            foreach ($iterator as $workflowItem) {
                $workflowItemIds[] = $workflowItem['id'];
                if (count($workflowItemIds) == self::DELETE_BATCH_SIZE) {
                    $this->clearWorkflowItems($entityName, $workflowItemIds);
                    $workflowItemIds = array();
                }
            }
            if ($workflowItemIds) {
                $this->clearWorkflowItems($entityName, $workflowItemIds);
            }
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param string $entityName
     * @param array $workflowItemIds
     */
    protected function clearWorkflowItems($entityName, array $workflowItemIds)
    {
        if (empty($workflowItemIds)) {
            return;
        }

        $workflowItemName = $this->getEntityName();
        $expressionBuilder = $this->createQueryBuilder('workflowItem')->expr();
        $entityManager = $this->getEntityManager();

        $updateCondition = $expressionBuilder->in('entity.workflowItem', $workflowItemIds);
        $updateDql = "UPDATE {$entityName} entity
            SET entity.workflowItem = NULL, entity.workflowStep = NULL
            WHERE {$updateCondition}";

        $deleteCondition = $expressionBuilder->in('workflowItem.id', $workflowItemIds);
        $deleteDql = "DELETE {$workflowItemName} workflowItem
            WHERE {$deleteCondition}";

        $entityManager->createQuery($updateDql)->execute();
        $entityManager->createQuery($deleteDql)->execute();
    }
}
