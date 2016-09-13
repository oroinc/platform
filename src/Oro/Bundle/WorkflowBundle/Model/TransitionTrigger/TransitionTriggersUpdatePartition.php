<?php

namespace Oro\Bundle\WorkflowBundle\Model\TransitionTrigger;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;

class TransitionTriggersUpdatePartition
{
    /**
     * @param ArrayCollection $existingTriggers
     * @param ArrayCollection $newTriggers
     * @return array A two elements array fist of which is a list of triggers that should be added, second - removed
     */
    public function partition(ArrayCollection $existingTriggers, ArrayCollection $newTriggers)
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

    private function contains(ArrayCollection $triggers, AbstractTransitionTrigger $trigger)
    {
        foreach ($triggers as $match) {
            if ($trigger->isEqualTo($match)) {
                return true;
            }
        }

        return false;
    }
}
