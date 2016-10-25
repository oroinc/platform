<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Translation\WorkflowDefinitionTranslationFieldsIterator;

class WorkflowDefinitionTranslationFieldsIteratorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider readWorkflowDefinitionFieldsProvider
     * @param WorkflowDefinition $definition
     * @param array $expected
     */
    public function testReadWorkflowDefinition(WorkflowDefinition $definition, array $expected)
    {
        $iterator = new WorkflowDefinitionTranslationFieldsIterator($definition);
        $this->assertEquals($expected, iterator_to_array($iterator));
    }

    /**
     * @return array
     */
    public function readWorkflowDefinitionFieldsProvider()
    {
        $definitionNormal = new WorkflowDefinition();
        $definitionNormal->setName('test_workflow');
        $definitionNormal->setLabel('workflow_label');
        $definitionNormal->addStep((new WorkflowStep())->setName('step_1')->setLabel('step_1_label'));
        $definitionNormal->addStep((new WorkflowStep())->setName('step_2')->setLabel('step_2_label'));
        $definitionNormal->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'label' => 'transition_1_label',
                        'message' => 'transition_1_message',
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
                'attributes' => [
                    'attribute_1' => [
                        'label' => 'attribute_1_label'
                    ]
                ]
            ]
        );

        $definitionLack = new WorkflowDefinition();
        $definitionLack->setName('test_workflow');
        $definitionLack->addStep((new WorkflowStep())->setName('step_1'));
        $definitionLack->addStep((new WorkflowStep())->setName('step_2'));
        $definitionLack->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => []
                ],
                'attributes' => [
                    'attribute_1' => []
                ]
            ]
        );

        return [
            'normal' => [
                'definition' => $definitionNormal,
                'expected' => [
                    'oro.workflow.test_workflow.label' => 'workflow_label',
                    'oro.workflow.test_workflow.transition.transition_1.label' => 'transition_1_label',
                    'oro.workflow.test_workflow.transition.transition_1.warning_message' => 'transition_1_message',
                    'oro.workflow.test_workflow.transition.transition_1.attribute.attribute_1.label' => 'TAL',
                    'oro.workflow.test_workflow.step.step_1.label' => 'step_1_label',
                    'oro.workflow.test_workflow.step.step_2.label' => 'step_2_label',
                    'oro.workflow.test_workflow.attribute.attribute_1.label' => 'attribute_1_label',
                ]
            ],
            'with lacks' => [
                'definition' => $definitionLack,
                'expected' => [
                    'oro.workflow.test_workflow.label' => null,
                    'oro.workflow.test_workflow.transition.transition_1.label' => null,
                    'oro.workflow.test_workflow.step.step_1.label' => null,
                    'oro.workflow.test_workflow.step.step_2.label' => null,
                    'oro.workflow.test_workflow.attribute.attribute_1.label' => null,
                    'oro.workflow.test_workflow.transition.transition_1.warning_message' => null
                ]
            ]
        ];
    }

    /**
     * @param WorkflowDefinition $actualDefinition
     * @param WorkflowDefinition $expectedDefinitionState
     * @dataProvider writeWorkflowDefinitionDataProvider
     */
    public function testWriteWorkflowDefinition(
        WorkflowDefinition $actualDefinition,
        WorkflowDefinition $expectedDefinitionState
    ) {
        $iterator = new WorkflowDefinitionTranslationFieldsIterator($actualDefinition);
        $values = [];
        foreach ($iterator as $key => $value) {
            $this->assertArrayNotHasKey($key, $values, 'Wont emit duplicates');
            $values[$key] = $value;
            $iterator->writeCurrent('*modified*' . $value);
        }

        $this->assertEquals($expectedDefinitionState, $actualDefinition);
    }

    /**
     * @return array
     */
    public function writeWorkflowDefinitionDataProvider()
    {
        return [
            'normal' => $this->createNormalCase(),
            'lack' => $this->createLackCase()
        ];
    }

    /**
     * @return array
     */
    private function createNormalCase()
    {
        $caseNormalActual = new WorkflowDefinition();
        $caseNormalActual->setName('test_workflow');
        $caseNormalActual->setLabel('workflow_label');
        $caseNormalActual->addStep((new WorkflowStep())->setName('step_1')->setLabel('step_1_label'));
        $caseNormalActual->addStep((new WorkflowStep())->setName('step_2')->setLabel('step_2_label'));
        $caseNormalActual->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'label' => 'transition_1_label',
                        'message' => 'transition_1_message',
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
                    'step_2' => [
                        'label' => 'step_2_label'
                    ]
                ],
                'attributes' => [
                    'attribute_1' => [
                        'label' => 'attribute_1_label'
                    ]
                ]
            ]
        );

        $caseNormalExpected = new WorkflowDefinition();
        $caseNormalExpected->setName('test_workflow');
        $caseNormalExpected->setLabel('*modified*workflow_label');
        $caseNormalExpected->addStep((new WorkflowStep())->setName('step_1')->setLabel('*modified*step_1_label'));
        $caseNormalExpected->addStep((new WorkflowStep())->setName('step_2')->setLabel('*modified*step_2_label'));
        $caseNormalExpected->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'label' => '*modified*transition_1_label',
                        'message' => '*modified*transition_1_message',
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute_1' => [
                                    'options' => [
                                        'label' => '*modified*TAL'
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
                        'label' => '*modified*step_2_label'
                    ]
                ],
                'attributes' => [
                    'attribute_1' => [
                        'label' => '*modified*attribute_1_label'
                    ]
                ]
            ]
        );

        return [
            'actual' => $caseNormalActual,
            'expected' => $caseNormalExpected
        ];
    }

    /**
     * @return array
     */
    private function createLackCase()
    {
        $caseLackActual = new WorkflowDefinition();
        $caseLackActual->setName('test_workflow');
        $caseLackActual->addStep((new WorkflowStep())->setName('step_1'));
        $caseLackActual->addStep((new WorkflowStep())
            ->setName('step_2')
            ->setLabel('wont_set_to_config_as_no_entry_there_for_the_step'));
        $caseLackActual->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute1' => [

                                ]
                            ]
                        ]
                    ]
                ],
                'steps' => [
                    'step_1' => []
                ],
                'attributes' => [
                    'attribute_1' => []
                ]
            ]
        );

        $caseLackExpected = new WorkflowDefinition();
        $caseLackExpected->setName('test_workflow');
        $caseLackExpected->setLabel('*modified*');
        $caseLackExpected->addStep((new WorkflowStep())->setName('step_1')->setLabel('*modified*'));
        $caseLackExpected->addStep((new WorkflowStep())
            ->setName('step_2')
            ->setLabel('*modified*wont_set_to_config_as_no_entry_there_for_the_step'));
        $caseLackExpected->setConfiguration(
            [
                'transitions' => [
                    'transition_1' => [
                        'label' => '*modified*',
                        'message' => '*modified*',
                        'form_options' => [
                            'attribute_fields' => [
                                'attribute1' => [
                                    'options' => [
                                        'label' => '*modified*'
                                    ]
                                ]
                            ]
                        ]
                    ]
                ],
                'steps' => [
                    'step_1' => [
                        'label' => '*modified*'
                    ]
                ],
                'attributes' => [
                    'attribute_1' => [
                        'label' => '*modified*'
                    ]
                ]
            ]
        );

        return [
            'actual' => $caseLackActual,
            'expected' => $caseLackExpected
        ];
    }
}
