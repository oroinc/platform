<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Transition service with default values for conditions.
 */
abstract class TransitionServiceAbstract implements TransitionServiceInterface
{
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return true;
    }

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return true;
    }
}
