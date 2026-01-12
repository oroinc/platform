<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

/**
 * Provides common functionality for start transition button provider extensions.
 *
 * This abstract class extends the base button provider extension to handle start transition buttons specifically.
 * It filters workflows based on exclusive record groups to ensure only compatible workflows are displayed
 * as available start transitions.
 */
abstract class AbstractStartTransitionButtonProviderExtension extends AbstractButtonProviderExtension
{
    #[\Override]
    public function supports(ButtonInterface $button)
    {
        return $button instanceof StartTransitionButton && $button->getTransition()->isStart();
    }

    #[\Override]
    protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    ) {
        return new StartTransitionButton($transition, $workflow, $buttonContext);
    }

    #[\Override]
    protected function getActiveWorkflows()
    {
        $exclusiveGroups = [];

        return parent::getActiveWorkflows()->filter(
            function (Workflow $workflow) use (&$exclusiveGroups) {
                $currentGroups = $workflow->getDefinition()->getExclusiveRecordGroups();

                if (array_intersect($exclusiveGroups, $currentGroups)) {
                    return false;
                }

                $exclusiveGroups = array_merge($exclusiveGroups, $currentGroups);

                return true;
            }
        );
    }
}
