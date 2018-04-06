<?php

namespace Oro\Bundle\WorkflowBundle\Helper;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class WorkflowAwareEntityFetcher
{
    use WorkflowQueryTrait;

    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param WorkflowDefinition $workflow
     * @param string $dqlFilter
     * @return array|object[]
     */
    public function getEntitiesWithoutWorkflowItem(WorkflowDefinition $workflow, $dqlFilter = '')
    {
        $qb = $this->getQueryBuilder($workflow->getRelatedEntity());

        $this->joinWorkflowItem($qb, 'wi')->where('wi.id IS NULL');

        if ($dqlFilter) {
            $qb->andWhere($dqlFilter);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @param string $entityClass
     * @return QueryBuilder
     */
    protected function getQueryBuilder($entityClass)
    {
        /** @var EntityManager $manager */
        $manager = $this->registry->getManagerForClass($entityClass);

        return $manager->createQueryBuilder()->select('e')->from($entityClass, 'e');
    }
}
