<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Provider;

use Oro\Bundle\WorkflowBundle\Provider\WorkflowItemVirtualRelationProvider;
use Oro\Bundle\WorkflowBundle\Provider\WorkflowStepVirtualRelationProvider;

class WorkflowStepVirtualRelationProviderTest extends AbstractVirtualRelationProviderTest
{
    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->provider = new WorkflowStepVirtualRelationProvider(
            $this->workflowManager,
            $this->doctrineHelper
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getVirtualRelations($className, $fieldName)
    {
        return [
            $this->provider->getRelationName() => [
                'label' => 'oro.workflow.workflowstep.entity_label',
                'relation_type' => 'OneToMany',
                'related_entity_name' => 'Oro\Bundle\WorkflowBundle\Entity\WorkflowStep',
                'query' => $this->getVirtualRelationsQuery($className, $fieldName),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getVirtualRelationsQuery($className, $fieldName)
    {
        return [
            'join' => [
                'left' => [
                    [
                        'join' => sprintf('%s.currentStep', WorkflowItemVirtualRelationProvider::RELATION_NAME),
                        'alias' => $this->provider->getRelationName(),
                    ]
                ]
            ]
        ];
    }
}
