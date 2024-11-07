<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Contracts\Service\ResetInterface;

/**
 * Provides information about running workflows for a specific entity.
 */
class RunningWorkflowProvider implements ResetInterface
{
    private array $runningWorkflowNames = [];

    public function __construct(
        private readonly DoctrineHelper $doctrineHelper
    ) {
    }

    public function getRunningWorkflowNames(object $entity): array
    {
        $entityClass = $this->doctrineHelper->getEntityClass($entity);
        $entityId = (string)$this->doctrineHelper->getSingleEntityIdentifier($entity);
        $cacheKey = $entityClass . '::' . $entityId;
        if (!isset($this->runningWorkflowNames[$cacheKey])) {
            $this->runningWorkflowNames[$cacheKey] = $this->doctrineHelper
                ->createQueryBuilder(WorkflowItem::class, 'wi')
                ->select('wi.workflowName')
                ->where('wi.entityClass = :entityClass AND wi.entityId = :entityId')
                ->setParameter('entityClass', $entityClass)
                ->setParameter('entityId', $entityId)
                ->getQuery()
                ->getSingleColumnResult();
        }

        return $this->runningWorkflowNames[$cacheKey];
    }

    #[\Override]
    public function reset(): void
    {
        $this->runningWorkflowNames = [];
    }
}
