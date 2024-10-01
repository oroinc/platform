<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Transition service with default values for conditions.
 */
abstract class TransitionServiceAbstract implements TransitionServiceInterface
{
    #[\Override]
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return true;
    }

    #[\Override]
    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool
    {
        return true;
    }

    #[\Override]
    public function execute(WorkflowItem $workflowItem): void
    {
    }
}
