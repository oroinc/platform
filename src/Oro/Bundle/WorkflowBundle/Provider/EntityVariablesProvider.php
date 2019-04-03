<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

use Oro\Bundle\EntityBundle\Twig\Sandbox\EntityVariablesProviderInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowTransitionRecord;

/**
 * The provider that allows to use WorkflowTransitionRecord in email templates.
 */
class EntityVariablesProvider implements EntityVariablesProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getVariableDefinitions(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableGetters(): array
    {
        return [
            WorkflowTransitionRecord::class => [
                'id'             => 'getId',
                'workflowItem'   => 'getWorkflowItem',
                'transitionName' => 'getTransitionName',
                'stepFrom'       => 'getStepFrom',
                'stepTo'         => 'getStepTo',
                'transitionDate' => 'getTransitionDate'
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getVariableProcessors(string $entityClass): array
    {
        return [];
    }
}
