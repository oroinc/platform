<?php

namespace Oro\Bundle\WorkflowBundle\Button;

class TransitionButton extends AbstractTransitionButton
{
    /**
     * {@inheritdoc}
     */
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
