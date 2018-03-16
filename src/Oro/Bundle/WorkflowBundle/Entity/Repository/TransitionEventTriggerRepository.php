<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\WorkflowBundle\Entity\EventTriggerInterface;
use Oro\Bundle\WorkflowBundle\Entity\TransitionEventTrigger;

class TransitionEventTriggerRepository extends EntityRepository implements EventTriggerRepositoryInterface
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
