<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;

class TriggerStub extends BaseTransitionTrigger
{
    /**
     * @param BaseTransitionTrigger $trigger
     * @return bool
     */
    protected function isEqualAdditionalFields(BaseTransitionTrigger $trigger)
    {
        return true;
    }
}
