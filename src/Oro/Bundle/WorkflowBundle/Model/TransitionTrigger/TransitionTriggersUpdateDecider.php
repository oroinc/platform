<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;

class TransitionTriggersUpdateDecider
{
    /**
     * @param array $existingTriggers
     * @param array $newTriggers
     * @return array[] A two elements array fist of which is a list of triggers that should be added, second - removed
     */
    public function decide(array &$existingTriggers, array &$newTriggers)
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

    private function contains(array &$triggers, AbstractTransitionTrigger $trigger)
    {
        foreach ($triggers as $match) {
            if ($trigger->isEqualTo($match)) {
                return true;
            }
        }

        return false;
    }
}
