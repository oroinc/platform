<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

/**
 * Interface for Transition services.
 */
interface TransitionServiceInterface
{
    public function isPreConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool;

    public function isConditionAllowed(WorkflowItem $workflowItem, Collection $errors = null): bool;

    public function execute(WorkflowItem $workflowItem): void;
}
