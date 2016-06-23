<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionSchedule;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\ORM\Query;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\WorkflowException;
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

        $identifier = $this->getIdentifierField($entityClass);

        $queryBuilder = $this->getEntityRepositoryForClass(WorkflowItem::class)
            ->createQueryBuilder('wi')
            ->select('wi.id')
            ->innerJoin('wi.definition', 'wd')
            ->innerJoin('wi.currentStep', 'ws')
            ->innerJoin(
                $entityClass,
                'e',
                Query\Expr\Join::WITH,
                sprintf('wi.entityId = e.%s', $identifier)
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

    /**
     * @param $entityClass
     * @return mixed
     * @throws WorkflowException
     */
    protected function getIdentifierField($entityClass)
    {
        /** @var ClassMetadataInfo $metadata */
        $metadata = $this->registry->getManagerForClass($entityClass)
            ->getClassMetadata($entityClass);

        if ($metadata->isIdentifierComposite) {
            throw new WorkflowException(
                sprintf(
                    'Entity `%s` transition query build failed. ' .
                    'Composite primary keys are not supported for workflow entities.',
                    $entityClass
                )
            );
        }

        $identifiers = $metadata->getIdentifierFieldNames();

        return $identifiers[0];
    }
}
