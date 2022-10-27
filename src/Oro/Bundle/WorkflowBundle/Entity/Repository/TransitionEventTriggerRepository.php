<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;

/**
 * Doctrine repository for TransitionEventTrigger entity
 */
class TransitionEventTriggerRepository extends ServiceEntityRepository implements EventTriggerRepositoryInterface
{
    /**
     * @param bool|null $enabled
     *
     * @return TransitionEventTrigger[]
     */
    public function findAllWithDefinitions($enabled = null)
    {
        $queryBuilder = $this->createQueryBuilder('trigger')
            ->select('trigger, definition')
            ->innerJoin('trigger.workflowDefinition', 'definition')
            ->orderBy('definition.priority');

        if (null !== $enabled) {
            $queryBuilder->andWhere('definition.active = :enabled')->setParameter('enabled', $enabled);
        }

        return $queryBuilder->getQuery()->execute();
    }

    /**
     * @return \Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger[]|EventTriggerInterface[]
     */
    public function getAvailableEventTriggers()
    {
        return $this->findAllWithDefinitions();
    }
}
