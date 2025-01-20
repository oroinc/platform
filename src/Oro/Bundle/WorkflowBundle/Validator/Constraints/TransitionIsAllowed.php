<?php

namespace Oro\Bundle\WorkflowBundle\Validator\Constraints;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Component\Validator\Constraint;

/**
 * Validation constraint checking if workflow transition is allowed.
 */
class TransitionIsAllowed extends Constraint
{
    public string $unknownTransitionMessage = 'oro.workflow.validator.transition.unknown';
    public string $notStartTransitionMessage = 'oro.workflow.validator.transition.not_start';
    public string $stepHasNotAllowedTransitionMessage = 'oro.workflow.validator.transition.step_not_allowed';
    public string $someConditionsNotMetMessage = 'oro.workflow.validator.transition.some_conditions_not_met';

    public function __construct(
        private readonly WorkflowItem $workflowItem,
        private readonly string $transitionName
    ) {
        parent::__construct();
    }

    public function getWorkflowItem(): WorkflowItem
    {
        return $this->workflowItem;
    }

    public function getTransitionName(): string
    {
        return $this->transitionName;
    }

    #[\Override]
    public function getTargets(): string|array
    {
        return self::CLASS_CONSTRAINT;
    }
}
