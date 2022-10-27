<?php

namespace Oro\Bundle\WorkflowBundle\Handler;

use Oro\Bundle\WorkflowBundle\Async\TransitionTriggerMessage;
use Oro\Bundle\WorkflowBundle\Entity\BaseTransitionTrigger;

/**
 * Such handlers perform the actual processing of transition trigger messages produced by various transition triggers.
*/
interface TransitionTriggerHandlerInterface
{
    /**
     * @param BaseTransitionTrigger $trigger
     * @param TransitionTriggerMessage $message
     * @return bool
     */
    public function process(BaseTransitionTrigger $trigger, TransitionTriggerMessage $message);
}
