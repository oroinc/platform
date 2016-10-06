<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;

class TransitionTriggersUpdateDecider
{
    /**
     * @param array $existingTriggers
     * @param array $newTriggers
     * @return array[] A two elements array first of which is a list of triggers that should be added, second - removed
     */
    public function decide(array $existingTriggers, array $newTriggers)
    {
        $remove = [];
        $add = [];

        foreach ($existingTriggers as $trigger) {
            if (!$this->contains($newTriggers, $trigger)) {
                $remove[] = $trigger;
            }
        }

        foreach ($newTriggers as $trigger) {
            if (!$this->contains($existingTriggers, $trigger)) {
                $add[] = $trigger;
            }
        }

        return [$add, $remove];
    }

    /**
     * @param array $triggers
     * @param BaseTransitionTrigger $trigger
     * @return bool
     */
    private function contains(array &$triggers, BaseTransitionTrigger $trigger)
    {
        foreach ($triggers as $match) {
            if ($trigger->isEqualTo($match)) {
                return true;
            }
        }

        return false;
    }
}
