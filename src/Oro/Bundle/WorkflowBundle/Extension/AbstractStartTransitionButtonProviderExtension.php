<?php

namespace Oro\Bundle\WorkflowBundle\Extension;

use Oro\Bundle\ActionBundle\Button\ButtonContext;
use Oro\Bundle\ActionBundle\Button\ButtonInterface;
use Oro\Bundle\WorkflowBundle\Button\StartTransitionButton;
use Oro\Bundle\WorkflowBundle\Model\Transition;
use Oro\Bundle\WorkflowBundle\Model\Workflow;

abstract class AbstractStartTransitionButtonProviderExtension extends AbstractButtonProviderExtension
{
    /**
     * {@inheritdoc}
     */
    public function supports(ButtonInterface $button)
    {
        return $button instanceof StartTransitionButton && $button->getTransition()->isStart();
    }

    /**
     * {@inheritdoc}
     */
    protected function createTransitionButton(
        Transition $transition,
        Workflow $workflow,
        ButtonContext $buttonContext
    ) {
        return new StartTransitionButton($transition, $workflow, $buttonContext);
    }

    /**
     * {@inheritdoc}
     */
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
