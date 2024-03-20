<?php

namespace Oro\Bundle\WorkflowBundle\Event\Transition;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Model\Transition;

class GuardEvent extends TransitionEvent
{
    public function __construct(
        WorkflowItem $workflowItem,
        Transition $transition,
        private bool $allowed,
        private ?Collection $errors = null
    ) {
        parent::__construct($workflowItem, $transition);
    }

    public function isAllowed(): bool
    {
        return $this->allowed;
    }

    public function setAllowed(bool $isAllowed): void
    {
        $this->allowed = $isAllowed;
    }

    public function getErrors(): ?Collection
    {
        return $this->errors;
    }
}
