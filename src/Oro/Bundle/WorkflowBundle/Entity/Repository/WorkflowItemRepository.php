<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;

class WorkflowItemRepository extends EntityRepository
{
    const DELETE_BATCH_SIZE = 1000;

    /**
     * Get workflow item associated with entity.
     *
     * @param string $entityClass
     * @param int $entityIdentifier
     * @return array|WorkflowItem[]
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier)
    {
        return $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier)->getQuery()->getResult();
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
            ->setParameter('entityId', (int)$entityIdentifier);

        return $qb;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return QueryBuilder
     */
    protected function getByDefinitionQueryBuilder(WorkflowDefinition $definition)
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
        //TODO: refactor or remove this method in story for CRM improvements
        $queryBuilder = $this->getByDefinitionQueryBuilder($definition);

        return $this->getEntityManager()->createQueryBuilder()
            ->update($definition->getRelatedEntity(), 'entity')
            ->set('entity.workflowStep', $definition->getStartStep()->getId())
            ->where('entity.workflowStep IS NULL')
            ->andWhere('entity.workflowItem IS NULL OR entity.workflowItem IN (' . $queryBuilder->getDQL() . ')')
            ->setParameters($queryBuilder->getParameters());
    }

    /**
     * @param string $entityClass
     * @param array $excludedWorkflowNames
     * @param int|null $batchSize
     * @throws \Exception
     */
    public function resetWorkflowData($entityClass, $excludedWorkflowNames = [], $batchSize = null)
    {
        $entityManager = $this->getEntityManager();
        $batchSize = $batchSize ?: self::DELETE_BATCH_SIZE;

        // select entities for reset
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('workflowItem.id')
            ->from('OroWorkflowBundle:WorkflowItem', 'workflowItem')
            ->innerJoin('workflowItem.definition', 'workflowDefinition')
            ->where('workflowItem.entityClass = ?1')
            ->setParameter(1, $entityClass)
            ->orderBy('workflowItem.id');

        if ($excludedWorkflowNames) {
            $queryBuilder->andWhere($queryBuilder->expr()->notIn('workflowDefinition.name', $excludedWorkflowNames));
        }

        $iterator = new DeletionQueryResultIterator($queryBuilder);
        $iterator->setBufferSize($batchSize);

        if ($iterator->count() == 0) {
            return;
        }

        // wrap all operation into transaction
        $entityManager->beginTransaction();
        try {
            // iterate over workflow items
            $workflowItemIds = [];
            foreach ($iterator as $workflowItem) {
                $workflowItemIds[] = $workflowItem['id'];
                if (count($workflowItemIds) == $batchSize) {
                    $this->clearWorkflowItems($workflowItemIds);
                    $workflowItemIds = [];
                }
            }
            if ($workflowItemIds) {
                $this->clearWorkflowItems($workflowItemIds);
            }
            $entityManager->commit();
        } catch (\Exception $e) {
            $entityManager->rollback();
            throw $e;
        }
    }

    /**
     * @param array $workflowItemIds
     */
    protected function clearWorkflowItems(array $workflowItemIds)
    {
        if (empty($workflowItemIds)) {
            return;
        }

        $expressionBuilder = $this->createQueryBuilder('workflowItem')->expr();
        $entityManager = $this->getEntityManager();

        $deleteCondition = $expressionBuilder->in('workflowItem.id', $workflowItemIds);
        $deleteDql = "DELETE OroWorkflowBundle:WorkflowItem workflowItem WHERE {$deleteCondition}";

        $entityManager->createQuery($deleteDql)->execute();
    }
}
