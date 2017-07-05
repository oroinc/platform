<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\EmailBundle\Provider\EntityVariablesProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;

class EntityVariablesProvider implements EntityVariablesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions($entityClass = null)
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters($entityClass = null)
    {
        return [
            WorkflowTransitionRecord::class => [
                'id' => 'getId',
                'workflowItem' => 'getWorkflowItem',
                'transitionName' => 'getTransitionName',
                'stepFrom' => 'getStepFrom',
                'stepTo' => 'getStepTo',
                'transitionDate' => 'getTransitionDate'
            ]
        ];
    }
}
