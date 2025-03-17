<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

/**
 * Provides a way to fetch workflow aware entities.
 */
class WorkflowAwareEntityFetcher
{
    use WorkflowQueryTrait;

    public function __construct(
        private ManagerRegistry $doctrine
    ) {
    }

    public function getEntitiesWithoutWorkflowItem(WorkflowDefinition $workflow, string $dqlFilter = ''): array
    {
        $entityClass = $workflow->getRelatedEntity();
        /** @var EntityManagerInterface $manager */
        $em = $this->doctrine->getManagerForClass($entityClass);
        $qb = $em->createQueryBuilder()
            ->select('e')
            ->from($entityClass, 'e');

        $this->joinWorkflowItem($qb, 'wi')->where('wi.id IS NULL');

        if ($dqlFilter) {
            $qb->andWhere($dqlFilter);
        }

        return $qb->getQuery()->getResult();
    }
}
