<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowBindEntity;

class WorkflowItemRepository extends EntityRepository
{
    /**
     * Get workflow items associated with entity.
     *
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param string|null $workflowName
     * @param string|null $workflowType
     * @return array
     */
    public function findByEntityMetadata($entityClass, $entityIdentifier, $workflowName = null, $workflowType = null)
    {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier, $workflowName, $workflowType);
        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param null|string $workflowName
     * @param null|string $workflowType
     * @param null|string $skippedWorkflow
     * @return int
     */
    public function checkWorkflowItemsByEntityMetadata(
        $entityClass,
        $entityIdentifier,
        $workflowName = null,
        $workflowType = null,
        $skippedWorkflow = null
    ) {
        $qb = $this->getWorkflowQueryBuilder($entityClass, $entityIdentifier, $workflowName, $workflowType);
        $qb->select('wi.id');

        if ($skippedWorkflow) {
            $qb->andWhere('wi.workflowName != :skippedWorkflowName')
                ->setParameter('skippedWorkflowName', $skippedWorkflow);
        }
        $qb->setMaxResults(1);

        return count($qb->getQuery()->getResult()) > 0;
    }

    /**
     * @param string $entityClass
     * @param string|array $entityIdentifier
     * @param null|string $workflowName
     * @param null|string $workflowType
     * @internal param null|string $skippedWorkflow
     * @return QueryBuilder
     */
    protected function getWorkflowQueryBuilder(
        $entityClass,
        $entityIdentifier,
        $workflowName = null,
        $workflowType = null
    ) {
        $entityIdentifierString = WorkflowBindEntity::convertIdentifiersToString($entityIdentifier);

        $qb = $this->getEntityManager()
            ->createQueryBuilder()
            ->select('wi')
            ->from('OroWorkflowBundle:WorkflowItem', 'wi')
            ->innerJoin('wi.bindEntities', 'wbe')
            ->where('wbe.entityClass = :entityClass')
            ->andWhere('wbe.entityId = :entityId')
            ->setParameter('entityClass', $entityClass)
            ->setParameter('entityId', $entityIdentifierString);

        if ($workflowName) {
            $qb->andWhere('wi.workflowName = :workflowName')
                ->setParameter('workflowName', $workflowName);
        }

        if ($workflowType) {
            $qb->innerJoin('wi.definition', 'wd')
                ->andWhere('wd.type = :workflowType')
                ->setParameter('workflowType', $workflowType);
        }

        return $qb;
    }
}
