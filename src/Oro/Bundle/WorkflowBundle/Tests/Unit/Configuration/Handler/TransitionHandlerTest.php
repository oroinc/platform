<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Configuration\Handler;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\TransitionHandler;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowConfiguration;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowTransitionType;

class TransitionHandlerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TransitionHandler */
    private $handler;

    protected function setUp(): void
    {
        $this->handler = new TransitionHandler();
    }

    /**
     * @dataProvider handleDataProvider
     */
    public function testHandle(array $expected, array $input)
    {
        $this->assertEquals($expected, $this->handler->handle($input));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function handleDataProvider(): array
    {
        return [
            'simple configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_STEPS => [
                        ['name' => 'test_step']
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        ['name' => 'test_attribute']
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'label' => 'Test Transition', //should be kept as filtering comes in separate service
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => [
                                        'options' => [
                                            'required' => true,
                                            'constraints' => [['NotBlank' => null]],
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        ['name' => 'test_transition_definition']
                    ]
                ],
                'input' => [
                    WorkflowConfiguration::NODE_STEPS => [
                        ['name' => 'test_step']
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        ['name' => 'test_attribute']
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'transition_definition' => 'test_transition_definition',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => [
                                        'options' => [
                                            'required' => true
                                        ],
                                    ]
                                ]
                            ]
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        ['name' => 'test_transition_definition']
                    ]
                ],
            ],
            'full configuration' => [
                'expected' => [
                    WorkflowConfiguration::NODE_STEPS => [
                        ['name' => 'test_step']
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        ['name' => 'test_attribute']
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'step_to' => 'test_step',
                            'is_start' => false,
                            'is_hidden' => false,
                            'is_unavailable_hidden' => true,
                            'acl_resource' => null,
                            'acl_message' => null,
                            'transition_definition' => 'test_transition_definition',
                            'frontend_options' => ['class' => 'btn-primary'],
                            'form_type' => WorkflowTransitionType::class,
                            'display_type' => 'dialog',
                            'label' => 'Test Transition',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => null,
                                ]
                            ],
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        ['name' => 'test_transition_definition']
                    ]
                ],
                'input' => [
                    WorkflowConfiguration::NODE_STEPS => [
                        ['name' => 'test_step']
                    ],
                    WorkflowConfiguration::NODE_ATTRIBUTES => [
                        ['name' => 'test_attribute']
                    ],
                    WorkflowConfiguration::NODE_TRANSITIONS => [
                        [
                            'name' => 'test_transition',
                            'label' => 'Test Transition',
                            'step_to' => 'test_step',
                            'is_start' => false,
                            'is_hidden' => false,
                            'is_unavailable_hidden' => true,
                            'acl_resource' => null,
                            'acl_message' => null,
                            'transition_definition' => 'test_transition_definition',
                            'frontend_options' => ['class' => 'btn-primary'],
                            'form_type' => WorkflowTransitionType::class,
                            'display_type' => 'dialog',
                            'form_options' => [
                                'attribute_fields' => [
                                    'test_attribute' => null
                                ]
                            ]
                        ]
                    ],
                    WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS => [
                        ['name' => 'test_transition_definition']
                    ]
                ],
            ]
        ];
    }

    public function testHandleEmptyConfiguration()
    {
        $configuration = [
            WorkflowConfiguration::NODE_STEPS => [
                ['name' => 'test_step']
            ],
            WorkflowConfiguration::NODE_TRANSITIONS => [
                [
                    'step_to' => 'test_step',
                ]
            ],
        ];

        $result = $this->handler->handle($configuration);

        $transitions = $result[WorkflowConfiguration::NODE_TRANSITIONS];
        $this->assertCount(1, $transitions);
        $transition = current($transitions);

        $this->assertArrayHasKey('name', $transition);
        $this->assertArrayHasKey('transition_definition', $transition);

        $this->assertStringStartsWith('transition_', $transition['name']);

        $this->assertStringStartsWith('transition_definition_', $transition['transition_definition']);
        $this->assertArrayHasKey(WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS, $result);

        $this->assertCount(1, $result[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]);
        $transitionDefinition = current($result[WorkflowConfiguration::NODE_TRANSITION_DEFINITIONS]);

        $this->assertArrayHasKey('name', $transitionDefinition);
        $this->assertEquals($transition['transition_definition'], $transitionDefinition['name']);
    }
}
