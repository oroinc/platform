<?php

namespace Oro\Bundle\WorkflowBundle\Button;

/**
 * Represents a workflow transition button for executing transitions on existing workflow items.
 *
 * This button extends the base transition button functionality to provide template data
 * specific to transitions on entities that already have an active workflow item.
 */
class TransitionButton extends AbstractTransitionButton
{
    #[\Override]
    public function getTemplateData(array $customData = [])
    {
        $workflowItem = $this->workflow->getWorkflowItemByEntityId($this->getButtonContext()->getEntityId());
        $templateData = parent::getTemplateData($customData);

        return array_merge_recursive(
            $templateData,
            [
                'routeParams' => [
                    'workflowItemId' => $workflowItem ? $workflowItem->getId() : null
                ]
            ]
        );
    }
}
