<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model\TransitionTrigger\Stub;

use Oro\Bundle\WorkflowBundle\Entity\AbstractTransitionTrigger;

class TriggerStub extends AbstractTransitionTrigger
{
    protected function isEqualAdditionalFields(AbstractTransitionTrigger $trigger)
    {
        return true;
    }
}
