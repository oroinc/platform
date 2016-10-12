<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\TranslationBundle\Translation\TranslationKeyGenerator;
use Oro\Bundle\TranslationBundle\Translation\TranslationKeySourceInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowTranslationFieldsIterator;

class WorkflowTranslationFieldsIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param array $config
     * @param array $readResultExpected
     * @dataProvider iterateReadCases
     */
    public function testIterateRead(array $config, array $readResultExpected)
    {
        $iterator = new WorkflowTranslationFieldsIterator(new TranslationKeyGenerator());

        $this->assertEquals($readResultExpected, iterator_to_array($iterator->iterateWorkflowConfiguration($config)));
    }

    /**
     * @return array
     */
    public function iterateReadCases()
    {
        return [
            'empty' => [
                'config' => ['name' => 'test_workflow'],
                'expected' => [
                    'oro.workflow.test_workflow.label' => null
                ]
            ],
            'full' => [
                'config' => [
                    'name' => 'test_workflow',
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
                            'message' => 'transition_1_message'
                        ],
                        'transition_2' => [
                            'label' => 'transition_2_label'
                        ],
                        'transition_3' => [
                            'message' => 'transition_3_message'
                        ],
                        'transition_four' => []
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => 'step_1_label'
                        ],
                        'step_2' => []
                    ]
                ],
                'expected' => [
                    'oro.workflow.test_workflow.label' => 'wf label',
                    'oro.workflow.test_workflow.attribute.attribute_1.label' => 'attribute_1_label',
                    'oro.workflow.test_workflow.attribute.attribute_2.label' => null,
                    'oro.workflow.test_workflow.transition.transition_1.label' => 'transition_1_label',
                    'oro.workflow.test_workflow.transition.transition_1.warning_message' => 'transition_1_message',
                    'oro.workflow.test_workflow.transition.transition_2.label' => 'transition_2_label',
                    'oro.workflow.test_workflow.transition.transition_2.warning_message' => null,
                    'oro.workflow.test_workflow.transition.transition_3.label' => null,
                    'oro.workflow.test_workflow.transition.transition_3.warning_message' => 'transition_3_message',
                    'oro.workflow.test_workflow.transition.transition_four.label' => null,
                    'oro.workflow.test_workflow.transition.transition_four.warning_message' => null,
                    'oro.workflow.test_workflow.step.step_1.label' => 'step_1_label',
                    'oro.workflow.test_workflow.step.step_2.label' => null,
                ]
            ]
        ];
    }

    /**
     * @param array $config
     * @param array $expected
     * @dataProvider iterateWriteCases
     */
    public function testIterateWrite(array $config, array $expected)
    {
        $iterator = new WorkflowTranslationFieldsIterator(new TranslationKeyGenerator());
        $i = 0;
        foreach ($iterator->iterateWorkflowConfiguration($config) as $source => &$value) {
            /**@var TranslationKeySourceInterface $source */
            $value = (string)$i++;
        }
        unset($value);

        $this->assertEquals($expected, $config);
    }
    /**
     * @return array
     */
    public function iterateWriteCases()
    {
        return [
            'empty' => [
                'config' => ['name' => 'test_workflow'],
                'expected' => ['name' => 'test_workflow', 'label' => '0']
            ],
            'full' => [
                'config' => [
                    'name' => 'test_workflow',
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
                            'message' => 'transition_1_message'
                        ],
                        'transition_2' => [
                            'label' => 'transition_2_label'
                        ],
                        'transition_3' => [
                            'message' => 'transition_3_message'
                        ],
                        'transition_four' => []
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => 'step_1_label'
                        ],
                        'step_2' => []
                    ]
                ],
                'expected' => [
                    'name' => 'test_workflow',
                    'label' => '0',
                    'transitions' => [
                        'transition_1' => [
                            'label' => '1',
                            'message' => '2'
                        ],
                        'transition_2' => [
                            'label' => '3',
                            'message' => '4'
                        ],
                        'transition_3' => [
                            'label' => '5',
                            'message' => '6'
                        ],
                        'transition_four' => [
                            'label' => '7',
                            'message' => '8'
                        ]
                    ],
                    'steps' => [
                        'step_1' => [
                            'label' => '9'
                        ],
                        'step_2' => [
                            'label' => '10'
                        ]
                    ],
                    'attributes' => [
                        'attribute_1' => [
                            'label' => '11'
                        ],
                        'attribute_2' => [
                            'label' => '12'
                        ]
                    ],
                ]
            ]
        ];
    }

    public function testIterateWorkflowDefinition()
    {
        $definition = new WorkflowDefinition();
        $definition->setName('test_workflow');
        $definition->setLabel('workflow_label');
        $definition->addStep((new WorkflowStep())->setName('step_1')->setLabel('step_1_label'));
        $definition->addStep((new WorkflowStep())->setName('step_2')->setLabel('step_2_label'));
        $definition->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'label' => 'transition_1_label',
                        'message' => 'transition_1_message'
                    ]
                ],
                'attributes' => [
                    'attribute_1' => [
                        'label' => 'attribute_1_label'
                    ]
                ]
            ]
        );

        $iterator = new WorkflowTranslationFieldsIterator(new TranslationKeyGenerator());

        $expected = [
            'oro.workflow.test_workflow.label' => 'workflow_label',
            'oro.workflow.test_workflow.transition.transition_1.label' => 'transition_1_label',
            'oro.workflow.test_workflow.transition.transition_1.warning_message' => 'transition_1_message',
            'oro.workflow.test_workflow.step.step_1.label' => 'step_1_label',
            'oro.workflow.test_workflow.step.step_2.label' => 'step_2_label',
            'oro.workflow.test_workflow.attribute.attribute_1.label' => 'attribute_1_label',
        ];

        $this->assertEquals($expected, iterator_to_array($iterator->iterateWorkflowDefinition($definition)));
    }
}
