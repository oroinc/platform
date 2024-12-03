<?php

namespace Oro\Bundle\WorkflowBundle\EventListener\Workflow;

use Oro\Bundle\WorkflowBundle\Event\Transition\PreAnnounceEvent;
use Oro\Component\ConfigExpression\ExpressionFactory;

/**
 * Check that workflow transition is allowed at least for one step.
 */
class TransitionAvailableStepsListener
{
    public function __construct(
        private ExpressionFactory $expressionFactory
    ) {
    }

    public function onPreAnnounce(PreAnnounceEvent $event): void
    {
        if (!$event->isAllowed()) {
            return;
        }

        $transition = $event->getTransition();
        $conditionalSteps = $transition->getConditionalStepsTo();

        $stepNames = array_merge([$transition->getStepTo()->getName()], array_keys($conditionalSteps));
        $isAllowed = false;
        foreach ($stepNames as $stepName) {
            $expression = $this->expressionFactory->create(
                'is_granted_workflow_transition',
                [$transition->getName(), $stepName]
            );
            $isAllowed = $expression->evaluate($event->getWorkflowItem());

            if ($isAllowed) {
                break;
            }
        }

        $event->setAllowed($isAllowed);
    }
}
