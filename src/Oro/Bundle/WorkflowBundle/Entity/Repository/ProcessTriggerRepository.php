<?php

namespace Oro\Bundle\WorkflowBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerRepository extends EntityRepository
{
    /**
     * @param ProcessTrigger $trigger
     * @return null|ProcessTrigger
     */
    public function findEqualTrigger(ProcessTrigger $trigger)
    {
        return $this->findOneBy(
            array(
                'event' => $trigger->getEvent(),
                'field' => $trigger->getField(),
                'definition' => $trigger->getDefinition(),
            )
        );
    }

    /**
     * @param ProcessTrigger $trigger
     * @return bool
     */
    public function isEqualTriggerExists(ProcessTrigger $trigger)
    {
        $equalTrigger = $this->findEqualTrigger($trigger);

        return !empty($equalTrigger);
    }

    /**
     * @return ProcessTrigger[]
     */
    public function findAllWithDefinitions()
    {
        return $this->createQueryBuilder('trigger')
            ->select('trigger, definition')
            ->innerJoin('trigger.definition', 'definition')
            ->andWhere('definition.enabled = :enabled')->setParameter('enabled', true)
            ->orderBy('definition.executionOrder')
            ->getQuery()
            ->execute();
    }
}
