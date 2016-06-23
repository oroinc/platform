<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Step;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

use Symfony\Bridge\Doctrine\ManagerRegistry;

class TransitionQueryFactory
{
    /** @var ManagerRegistry */
    private $registry;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->registry = $managerRegistry;
    }

    /**
     * @param Workflow $workflow
     * @param string $transitionName
     * @param null $dqlFilter
     * @return Query
     */
    public function create(Workflow $workflow, $transitionName, $dqlFilter = null)
    {
        $steps = $workflow->getStepManager()->getRelatedTransitionSteps($transitionName)->map(
            function (Step $step) {
                return $step->getName();
            }
        );

        $entityClass = $workflow->getDefinition()->getRelatedEntity();

        $queryBuilder = $this->getEntityRepositoryForClass(WorkflowItem::class)
            ->createQueryBuilder('wi')
            ->select('wi.id')
            ->innerJoin('wi.definition', 'wd')
            ->innerJoin('wi.currentStep', 'ws')
            ->innerJoin(
                $entityClass,
                'e',
                Query\Expr\Join::WITH,
                'wi.entityId = IDENTITY(e)'
            );

        $queryBuilder->where($queryBuilder->expr()->in('ws.name', ':workflowSteps'))
            ->setParameter('workflowSteps', $steps->getValues());

        $queryBuilder->andWhere('wd.relatedEntity = :entityClass')
            ->setParameter('entityClass', $entityClass);

        if ($dqlFilter) {
            $queryBuilder->andWhere($dqlFilter);
        }

        return $queryBuilder->getQuery();
    }

    /**
     * @param string $entityClass
     * @return EntityRepository
     */
    private function getEntityRepositoryForClass($entityClass)
    {
        return $this->registry->getManagerForClass($entityClass)->getRepository($entityClass);
    }
}
