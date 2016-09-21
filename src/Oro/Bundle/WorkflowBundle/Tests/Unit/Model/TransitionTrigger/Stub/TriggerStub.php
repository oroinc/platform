<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;

class TriggerStub extends AbstractTransitionTrigger
{
    /**
     * @param AbstractTransitionTrigger $trigger
     * @return bool
     */
    protected function isEqualAdditionalFields(AbstractTransitionTrigger $trigger)
    {
        return true;
    }
}
