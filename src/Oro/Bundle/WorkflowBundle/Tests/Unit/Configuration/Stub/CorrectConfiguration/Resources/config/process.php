<?php

use Oro\Bundle\WorkflowBundle\Configuration\ProcessConfigurationProvider;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

return array(
    ProcessConfigurationProvider::NODE_DEFINITIONS => array(
        'test_definition' => array(
            'label' => 'Test Definition',
            'enabled' => true,
            'entity' => 'Oro\Bundle\UserBundle\Entity\User',
            'order' => 20,
            'execution_required' => true,
            'actions_configuration' => array(
                array('@assign_value' => array('$entity.field', 'value'))
            )
        ),
        'another_definition' => array(
            'label' => 'Another definition',
            'enabled' => true,
            'entity' => 'My\Entity',
            'order' => 0,
            'execution_required' => false,
            'actions_configuration' => array()
        ),
    ),
    ProcessConfigurationProvider::NODE_TRIGGERS => array(
        'test_definition' => array(
            array(
                'event' => ProcessTrigger::EVENT_UPDATE,
                'field' => 'some_field',
                'time_shift' => 123456,
            ),
            array(
                'event' => ProcessTrigger::EVENT_CREATE,
                'field' => null,
                'time_shift' => 189302700,
            ),
            array(
                'event' => ProcessTrigger::EVENT_DELETE,
                'field' => null,
                'time_shift' => null,
            )
        )
    ),
);
