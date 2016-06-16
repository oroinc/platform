<?php

namespace Oro\Bundle\WorkflowBundle\Provider;

class WorkflowStepVirtualRelationProvider extends AbstractVirtualRelationProvider
{
    const RELATION_NAME = 'workflowSteps_virtual';

    /**
     * {@inheritdoc}
     */
    protected function getRelationName()
    {
        return self::RELATION_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRelationDefinition($className, $idField)
    {
        return [
            'label' => 'oro.workflow.workflowstep.entity_label',
            'relation_type' => 'OneToMany',
            'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
            'query' => [
                'join' => [
                    'left' => [
                        [
                            'join' => sprintf('%s.currentStep', WorkflowItemVirtualRelationProvider::RELATION_NAME),
                            'alias' => self::RELATION_NAME,
                        ]
                    ]
                ]
            ]
        ];
    }
}
