<?php

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Configuration\ProcessPriority;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

return [
    ProcessConfigurationProvider::NODE_DEFINITIONS => [
        'test_definition' => [
            'label'   => 'Test Definition',
            'enabled' => true,
            'entity'  => 'Oro\Bundle\UserBundle\Entity\User',
            'order'   => 20,
            'exclude_definitions'   => [],
            'actions_configuration' => [
                ['@assign_value' => ['$entity.field', 'value']]
            ],
            'preconditions' => []
        ],
        'another_definition' => [
            'label'                 => 'Another definition',
            'entity'                => 'My\Entity',
            'actions_configuration' => [],
            'enabled'               => true,
            'order'                 => 0,
            'exclude_definitions'   => [],
            'preconditions' => []
        ]
    ],
    ProcessConfigurationProvider::NODE_TRIGGERS => [
        'test_definition' => [
            [
                'event'      => ProcessTrigger::EVENT_UPDATE,
                'field'      => 'some_field',
                'priority'   => 10,
                'queued'     => true,
                'time_shift' => 123456,
                'cron'       => null
            ],
            [
                'event'      => ProcessTrigger::EVENT_CREATE,
                'queued'     => true,
                'time_shift' => 86700,
                'field'      => null,
                'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                'cron'       => null
            ],
            [
                'event'      => ProcessTrigger::EVENT_DELETE,
                'field'      => null,
                'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                'queued'     => false,
                'time_shift' => null,
                'cron'       => null
            ],
            [
                'event'      => null,
                'field'      => null,
                'priority'   => ProcessPriority::PRIORITY_DEFAULT,
                'queued'     => false,
                'time_shift' => null,
                'cron'       => '*/1 * * * *'
            ]
        ]
    ]
];
