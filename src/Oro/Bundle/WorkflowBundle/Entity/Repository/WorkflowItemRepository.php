<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\BatchBundle\ORM\Query\DeletionQueryResultIterator;

class WorkflowItemRepository extends EntityRepository
{
    const DELETE_BATCH_SIZE = 1000;

    /**
     * Returns all available workflow items for given entity id & entity class
     *
     * @param $entityClass
     * @param $entityIdentifier
     * @return array|WorkflowItem[]
     */
    public function findAllByEntityMetadata($entityClass, $entityIdentifier)
    {
        return $this->findBy([
            'entityId' => $entityIdentifier,
            'entityClass' => $entityClass,
        ]);
    }

    /**
     * Returns named workflow item by given entity id & entity class
     *
     * @param $entityClass
     * @param $entityIdentifier
     * @param $workflowName
     * @return array|WorkflowItem[]
     */
    public function findOneByEntityMetadata($entityClass, $entityIdentifier, $workflowName)
    {
        return $this->findOneBy([
            'entityId' => $entityIdentifier,
            'entityClass' => $entityClass,
            'workflowName' => $workflowName,
        ]);
    }

    /**
     * Returns all found workflow items associated with entity.
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
            ->setParameter('entityId', (string)$entityIdentifier);

        return $qb;
    }

    /**
     * @param WorkflowDefinition $definition
     * @return QueryBuilder
     */
    public function getEntityWorkflowStepUpgradeQueryBuilder(WorkflowDefinition $definition)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->update(WorkflowItem::class, 'workflowItem')
            ->set('workflowItem.currentStep', $definition->getStartStep()->getId())
            ->where('workflowItem.currentStep IS NULL')
            ->andWhere('workflowItem.definition = :definition')
            ->setParameter('definition', $definition);
    }

    /**
     * @param WorkflowDefinition $workflowDefinition
     * @param int|null $batchSize
     *
     * @throws \Exception
     */
    public function resetWorkflowData(WorkflowDefinition $workflowDefinition, $batchSize = null)
    {
        $entityManager = $this->getEntityManager();
        $batchSize = (int) ($batchSize ?: self::DELETE_BATCH_SIZE);

        // select entities for reset
        $queryBuilder = $this->getEntityManager()->createQueryBuilder();
        $queryBuilder->select('workflowItem.id')
            ->from('OroWorkflowBundle:WorkflowItem', 'workflowItem')
            ->innerJoin('workflowItem.definition', 'workflowDefinition', Join::WITH, 'workflowDefinition.name = ?1')
            ->setParameter(1, $workflowDefinition->getName())
            ->orderBy('workflowItem.id');

        $iterator = new DeletionQueryResultIterator($queryBuilder);
        $iterator->setBufferSize($batchSize);

        if ($iterator->count() === 0) {
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

    /**
     * @param string $entityClass
     * @param array $entityIds
     * @return array
     */
    public function getGroupedWorkflowNameAndWorkflowStepName($entityClass, array $entityIds)
    {
        $entityIds = array_map(function ($item) {
            return (string)$item;
        }, $entityIds);

        $qb = $this->createQueryBuilder('wi');
        $qb->select('wi.entityId AS entityId, d.label AS workflowName, ws.label AS stepName')
            ->join('wi.currentStep', 'ws')
            ->join('wi.definition', 'd')
            ->where($qb->expr()->eq('wi.entityClass', ':entityClass'))
            ->andWhere($qb->expr()->in('wi.entityId', ':entityId'))
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIds)
        ;

        $items = $qb->getQuery()->getArrayResult();

        $result = [];
        foreach ($items as $item) {
            $result[$item['entityId']][] = $item;
        }

        return $result;
    }

    /**
     * @param string $entityClass
     * @param array $workflowStepIds
     * @return array
     */
    public function getEntityIdsByEntityClassAndWorkflowStepIds($entityClass, array $workflowStepIds)
    {
        $qb = $this->createQueryBuilder('wi');
        $qb->select('wi.entityId AS id')
            ->join('wi.currentStep', 'ws')
            ->where(
                $qb->expr()->eq('wi.entityClass', ':entityClass'),
                $qb->expr()->in('ws.id', ':stepIds')
            )
            ->setParameter('entityClass', $entityClass)
            ->setParameter('stepIds', $workflowStepIds);

        return array_map(
            function ($item) {
                return $item['id'];
            },
            $qb->getQuery()->getArrayResult()
        );
    }

    /**
     * @param string $entityClass
     * @param array $workflowNames
     * @return array
     */
    public function getEntityIdsByEntityClassAndWorkflowNames($entityClass, array $workflowNames)
    {
        $qb = $this->createQueryBuilder('wi');
        $qb->select('wi.entityId AS id')
            ->where(
                $qb->expr()->eq('wi.entityClass', ':entityClass'),
                $qb->expr()->in('IDENTITY(wi.definition)', ':workflowNames')
            )
            ->setParameter('entityClass', $entityClass)
            ->setParameter('workflowNames', $workflowNames);

        return array_map(
            function ($item) {
                return $item['id'];
            },
            $qb->getQuery()->getArrayResult()
        );
    }
}
