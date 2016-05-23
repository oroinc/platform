<?php

namespace Oro\Bundle\WorkflowBundle\Generator;

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;

class ProcessConfigurationGenerator
{
    /**
     * @param WorkflowDefinition $workflowDefinition
     * @return array
     */
    public function generateForScheduledTransition(WorkflowDefinition $workflowDefinition)
    {
        return [
            ProcessConfigurationProvider::NODE_DEFINITIONS => [
                'test_definition' => [
                    'label' => 'Test Definition',
                    'enabled' => true,
                    'entity' => 'Oro\Bundle\UserBundle\Entity\User',
                    'actions_configuration' => [
                        ['@assign_value' => ['$entity.field', 'value']]
                    ]
                ]
            ],
            ProcessConfigurationProvider::NODE_TRIGGERS => [
                'test_definition' => [
                    ['cron' => '*/1 * * * *']
                ]
            ]
        ];
    }
}
