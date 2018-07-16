<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Translation\WorkflowConfigurationTranslationFieldsIterator;

class WorkflowConfigurationTranslationFieldsIteratorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @param string $workflowName
     * @param array $config
     * @param array $readResultExpected
     * @dataProvider iterateReadCases
     */
    public function testIterateRead($workflowName, array $config, array $readResultExpected)
    {
        $iterator = new WorkflowConfigurationTranslationFieldsIterator($workflowName, $config);

        $this->assertEquals(
            $readResultExpected,
            iterator_to_array($iterator)
        );
    }

    /**
     * @return array
     */
    public function iterateReadCases()
    {
        return [
            'empty' => [
                'workflow_name' => 'test_workflow',
                'config' => [],
                'expected' => [
                    'oro.workflow.test_workflow.label' => null
                ]
            ],
            'full' => [
                'workflow_name' => 'test_workflow',
                'config' => [
                    'label' => 'wf label',
                    'attributes' => [
                        'attribute_1' => [
                            'label' => 'attribute_1_label'
                        ],
                        'attribute_2' => []
                    ],
                    'transitions' => [
                        'transition_1' => [
                            'label' => 'transition_1_label',
                            'button_label' => 'transition_1_button_label',
                            'button_title' => 'transition_1_button_title',
                            'message' => 'transition_1_message'
                        ],
                        'transition_2' => [
                            'label' => 'transition_2_label'
                        ],
                        'transition_3' => [
                            'message' => 'transition_3_message'
                        ],
                        'transition_four' => [
                            'form_options' => [
                                'attribute_fields' => [
                                    'attribute_1' => [
                                        'options' => [
                                            'label' => 'TAL'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => 'step_1_label'
                        ],
                        'step_2' => []
                    ],
                    'variable_definitions' => [
                        'variables' => [
                            'var1' => [
                                'label' => 'my_var'
                            ]
                        ]
                    ]
                ],
                'expected' => [
                    'oro.workflow.test_workflow.label' => 'wf label',
                    'oro.workflow.test_workflow.attribute.attribute_1.label' => 'attribute_1_label',
                    'oro.workflow.test_workflow.attribute.attribute_2.label' => null,
                    'oro.workflow.test_workflow.transition.transition_1.label' => 'transition_1_label',
                    'oro.workflow.test_workflow.transition.transition_1.warning_message' => 'transition_1_message',
                    'oro.workflow.test_workflow.transition.transition_2.label' => 'transition_2_label',
                    'oro.workflow.test_workflow.transition.transition_3.label' => null,
                    'oro.workflow.test_workflow.transition.transition_3.warning_message' => 'transition_3_message',
                    'oro.workflow.test_workflow.transition.transition_four.label' => null,
                    'oro.workflow.test_workflow.transition.transition_four.attribute.attribute_1.label' => 'TAL',
                    'oro.workflow.test_workflow.step.step_1.label' => 'step_1_label',
                    'oro.workflow.test_workflow.step.step_2.label' => null,
                    'oro.workflow.test_workflow.transition.transition_2.warning_message' => null,
                    'oro.workflow.test_workflow.transition.transition_four.warning_message' => null,
                    'oro.workflow.test_workflow.variable.var1.label' => 'my_var',
                    'oro.workflow.test_workflow.transition.transition_1.button_label' => 'transition_1_button_label',
                    'oro.workflow.test_workflow.transition.transition_1.button_title' => 'transition_1_button_title',
                    'oro.workflow.test_workflow.transition.transition_2.button_label' => null,
                    'oro.workflow.test_workflow.transition.transition_2.button_title' => null,
                    'oro.workflow.test_workflow.transition.transition_3.button_label' => null,
                    'oro.workflow.test_workflow.transition.transition_3.button_title' => null,
                    'oro.workflow.test_workflow.transition.transition_four.button_label' => null,
                    'oro.workflow.test_workflow.transition.transition_four.button_title' => null
                ]
            ]
        ];
    }

    /**
     * @param string $workflowName
     * @param array $config
     * @param array $expected
     * @dataProvider iterateWriteCases
     */
    public function testIterateWrite($workflowName, array $config, array $expected)
    {
        $iterator = new WorkflowConfigurationTranslationFieldsIterator($workflowName, $config);

        foreach ($iterator as $value) {
            $iterator->writeCurrent('*modified*' . $value);
        }

        $this->assertEquals($expected, $iterator->getConfiguration());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function iterateWriteCases()
    {
        return [
            'empty' => [
                'workflow_name' => 'test_workflow',
                'config' => [],
                'expected' => ['label' => '*modified*']
            ],
            'full' => [
                'workflow_name' => 'test_workflow',
                'config' => [
                    'label' => 'wf label',
                    'attributes' => [
                        'attribute_1' => [
                            'label' => 'attribute_1_label'
                        ],
                        'attribute_2' => []
                    ],
                    'transitions' => [
                        'transition_1' => [
                            'label' => 'transition_1_label',
                            'button_label' => 'transition_1_button_label',
                            'button_title' => 'transition_1_button_title',
                            'message' => 'transition_1_message'
                        ],
                        'transition_2' => [
                            'label' => 'transition_2_label'
                        ],
                        'transition_3' => [
                            'message' => 'transition_3_message'
                        ],
                        'transition_four' => [
                            'form_options' => [
                                'attribute_fields' => [
                                    'attribute_1' => [
                                        'options' => [
                                            'label' => 'transition_attribute_1_field_label'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => 'step_1_label'
                        ],
                        'step_2' => []
                    ],
                    'variable_definitions' => [
                        'variables' => [
                            'var1' => [
                                'label' => 'my_var'
                            ],
                            'var2' => []
                        ]
                    ]
                ],
                'expected' => [
                    'label' => '*modified*wf label',
                    'transitions' => [
                        'transition_1' => [
                            'label' => '*modified*transition_1_label',
                            'button_label' => '*modified*transition_1_button_label',
                            'button_title' => '*modified*transition_1_button_title',
                            'message' => '*modified*transition_1_message'
                        ],
                        'transition_2' => [
                            'label' => '*modified*transition_2_label',
                            'button_label' => '*modified*',
                            'button_title' => '*modified*',
                            'message' => '*modified*'
                        ],
                        'transition_3' => [
                            'label' => '*modified*',
                            'button_label' => '*modified*',
                            'button_title' => '*modified*',
                            'message' => '*modified*transition_3_message'
                        ],
                        'transition_four' => [
                            'label' => '*modified*',
                            'button_label' => '*modified*',
                            'button_title' => '*modified*',
                            'message' => '*modified*',
                            'form_options' => [
                                'attribute_fields' => [
                                    'attribute_1' => [
                                        'options' => [
                                            'label' => '*modified*transition_attribute_1_field_label'
                                        ]
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => '*modified*step_1_label'
                        ],
                        'step_2' => [
                            'label' => '*modified*'
                        ]
                    ],
                    'attributes' => [
                        'attribute_1' => [
                            'label' => '*modified*attribute_1_label'
                        ],
                        'attribute_2' => [
                            'label' => '*modified*'
                        ]
                    ],
                    'variable_definitions' => [
                        'variables' => [
                            'var1' => [
                                'label' => '*modified*my_var'
                            ],
                            'var2' => [
                                'label' => '*modified*'
                            ]
                        ]
                    ]
                ]
            ]
        ];
    }
}
