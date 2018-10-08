<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

class TranslationProcessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslationProcessor */
    private $processor;

    /** @var WorkflowTranslationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $translationHelper;

    protected function setUp()
    {
        $this->translationHelper = $this->getMockBuilder(WorkflowTranslationHelper::class)
            ->disableOriginalConstructor()->getMock();

        $this->processor = new TranslationProcessor($this->translationHelper);
    }

    protected function tearDown()
    {
        unset($this->translationHelper, $this->processor);
    }

    public function testImplementsInterfaces()
    {
        $this->assertInstanceOf(WorkflowDefinitionBuilderExtensionInterface::class, $this->processor);
        $this->assertInstanceOf(ConfigurationHandlerInterface::class, $this->processor);
    }

    public function testPrepare()
    {
        $config = ['label' => 24];
        $result = $this->processor->prepare('test_workflow', $config);

        $this->assertEquals(
            $result,
            ['label' => 'oro.workflow.test_workflow.label'],
            'should return modified with key configuration back'
        );
    }

    public function testHandle()
    {
        $configuration = [
            'name' => 'test_workflow',
            null,
            'label' => 'wflabel',
            'transitions' => [
                'test_transition' => [
                    'label' => 'test_transition',
                    'button_label' => 'oro.workflow.test_workflow.transition.test_transition.button_label',
                    'button_title' => 'oro.workflow.test_workflow.transition.test_transition.button_title',
                    'message' => 'oro.workflow.test_workflow.transition.test_transition.warning_message',
                ]
            ]
        ];

        $this->translationHelper->expects($this->at(0))
            ->method('saveTranslation')
            ->with('oro.workflow.test_workflow.label', 'wflabel');
        $this->translationHelper->expects($this->at(1))
            ->method('saveTranslation')
            ->with('oro.workflow.test_workflow.transition.test_transition.label', 'test_transition');
        $this->translationHelper->expects($this->at(2))
            ->method('saveTranslationAsSystem')
            ->with('oro.workflow.test_workflow.transition.test_transition.button_label', '');
        $this->translationHelper->expects($this->at(3))
            ->method('saveTranslationAsSystem')
            ->with('oro.workflow.test_workflow.transition.test_transition.button_title', '');
        $this->translationHelper->expects($this->at(4))
            ->method('saveTranslationAsSystem')
            ->with('oro.workflow.test_workflow.transition.test_transition.warning_message', '');

        $this->assertEquals($configuration, $this->processor->handle($configuration));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Workflow configuration for handler must contain valid `name` node.
     */
    public function testHandleIncorrectConfigFormatException()
    {
        $this->processor->handle([]);
    }

    public function testTranslateWorkflowDefinitionFieldsWithoutWorkflowName()
    {
        $this->translationHelper->expects($this->never())->method($this->anything());

        $definition = new WorkflowDefinition();
        $definition->setLabel('stored_label');

        $this->processor->translateWorkflowDefinitionFields($definition);

        $expected = new WorkflowDefinition();
        $expected->setLabel('stored_label');

        $this->assertEquals($expected, $definition);
    }

    /**
     * @dataProvider translateWorkflowDefinitionFieldsProvider
     *
     * @param WorkflowDefinition $expected
     * @param WorkflowDefinition $definition
     * @param array $values
     * @param bool $useKeyAsTranslation
     */
    public function testTranslateWorkflowDefinitionFields(
        WorkflowDefinition $expected,
        WorkflowDefinition $definition,
        array $values,
        $useKeyAsTranslation
    ) {
        $this->translationHelper->expects($this->any())->method('findWorkflowTranslation')->willReturnMap($values);

        $this->processor->translateWorkflowDefinitionFields($definition, $useKeyAsTranslation);

        $this->assertEquals($expected, $definition);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function translateWorkflowDefinitionFieldsProvider()
    {
        $definition = new WorkflowDefinition();
        $definition->setName('test_workflow');
        $definition->setLabel('stored_label_key1');
        $definition->addStep((new WorkflowStep())->setName('step1')->setLabel('step1_stored_label_key'));
        $definition->addStep((new WorkflowStep())->setName('step2')->setLabel('step2_stored_label_key'));
        $definition->setConfiguration([
            'transitions' => [
                'transition1' => [
                    'label' => 'transition1_stored_label_key',
                    'button_label' => 'transition1_stored_button_label_key',
                    'button_title' => 'transition1_stored_button_title_key',
                    'message' => 'message1_stored_label_key',
                    'form_options' => [
                        'attribute_fields' => [
                            'attribute1' => [
                                'options' => [
                                    'label' => 'transition1_attribute1_stored_label_key'
                                ]
                            ]
                        ]
                    ]
                ],
                'transition2' => [
                    'label' => 'transition2_stored_label_key',
                    'message' => 'oro.workflow.test_workflow.transition.transition2.warning_message',
                ]
            ],
            'steps' => [
                'step1' => [],
                'step2' => []
            ],
            'attributes' => [
                'attribute1' => ['label' => 'attribute1_stored_label_key'],
                'attribute2' => [/*null case*/]
            ]
        ]);
        $expected = new WorkflowDefinition();
        $expected->setName('test_workflow');
        $expected->setLabel('translated_label_key');
        $expected->addStep((new WorkflowStep())->setName('step1')->setLabel('translated_step1_stored_label_key'));
        $expected->addStep((new WorkflowStep())->setName('step2')->setLabel('translated_step2_stored_label_key'));
        $expected->setConfiguration([
            'transitions' => [
                'transition1' => [
                    'label' => 'translated_transition1_stored_label_key',
                    'button_label' => 'translated_transition1_stored_button_label_key',
                    'button_title' => 'translated_transition1_stored_button_title_key',
                    'message' => 'translated_message1_stored_label_key',
                    'form_options' => [
                        'attribute_fields' => [
                            'attribute1' => [
                                'options' => [
                                    'label' => 'translated_transition1_attribute1_stored_label_key'
                                ]
                            ]
                        ]
                    ]
                ],
                'transition2' => [
                    'label' => 'translated_transition2_stored_label_key',
                    'button_label' => '',
                    'button_title' => '',
                    'message' => '',
                ]
            ],
            'steps' => [ //this node would have same values as entities
                'step1' => ['label' => 'translated_step1_stored_label_key'],
                'step2' => ['label' => 'translated_step2_stored_label_key']
            ],
            'attributes' => [
                'attribute1' => ['label' => 'translated_attribute1_stored_label_key'],
                'attribute2' => ['label' => '']

            ]
        ]);


        $keyedConfig = [
            'transitions' => [
                'transition1' => [
                    'label' => 'oro.workflow.test_workflow.transition.transition1.label',
                    'button_label' => 'buttonLabel1',
                    'button_title' => 'buttonTitle1',
                    'message' => 'Message1',
                    'form_options' => [
                        'attribute_fields' => [
                            'attribute1' => [
                                'options' => [
                                    'label' =>
                                        'oro.workflow.test_workflow.transition.transition1.attribute.attribute1.label'
                                ]
                            ]
                        ]
                    ]
                ],
            ],
            'attributes' => [
                'attribute1' => ['label' => 'oro.workflow.test_workflow.attribute.attribute1.label'],
                'attribute2' => ['label' => '']

            ]
        ];
        $definitionKeys = new WorkflowDefinition();
        $definitionKeys->setName('test_workflow');
        $definitionKeys->setLabel('oro.workflow.test_workflow.label');
        $definitionKeys->addStep(
            (new WorkflowStep())->setName('step1')->setLabel('oro.workflow.test_workflow.step.step1.label')
        );
        $definitionKeys->setConfiguration($keyedConfig);

        $definitionKeysExpected = new WorkflowDefinition();
        $definitionKeysExpected->setName('test_workflow');
        $definitionKeysExpected->setLabel('oro.workflow.test_workflow.label');
        $definitionKeysExpected->addStep(
            (new WorkflowStep())->setName('step1')->setLabel('oro.workflow.test_workflow.step.step1.label')
        );
        $keyedConfig['transitions']['transition1']['button_label'] = '';
        $keyedConfig['transitions']['transition1']['button_title'] = '';
        $keyedConfig['transitions']['transition1']['message'] = '';
        $definitionKeysExpected->setConfiguration($keyedConfig);

        return [
            'full case' => [
                'expected' => $expected,
                'definition' => $definition,
                'values' => [
                    ['stored_label_key1', 'test_workflow', null, 'translated_label_key'],
                    ['step1_stored_label_key', 'test_workflow', null, 'translated_step1_stored_label_key'],
                    ['step2_stored_label_key', 'test_workflow', null, 'translated_step2_stored_label_key'],
                    ['transition1_stored_label_key', 'test_workflow', null, 'translated_transition1_stored_label_key'],
                    [
                        'transition1_stored_button_label_key',
                        'test_workflow',
                        null,
                        'translated_transition1_stored_button_label_key'
                    ],
                    [
                        'transition1_stored_button_title_key',
                        'test_workflow',
                        null,
                        'translated_transition1_stored_button_title_key'
                    ],
                    ['message1_stored_label_key', 'test_workflow', null, 'translated_message1_stored_label_key'],
                    [
                        'transition1_attribute1_stored_label_key',
                        'test_workflow',
                        null,
                        'translated_transition1_attribute1_stored_label_key'
                    ],
                    ['transition2_stored_label_key', 'test_workflow', null, 'translated_transition2_stored_label_key'],
                    ['message2_stored_label_key', 'test_workflow', null, 'message2_stored_label_key'],
                    //same means no translation found
                    ['attribute1_stored_label_key', 'test_workflow', null, 'translated_attribute1_stored_label_key'],
                    [null, null, null]
                ],
                'useKeyAsTranslation' => false,
            ],
            'full case use keys as translations' => [
                'expected' => $definitionKeysExpected,
                'definition' => $definitionKeys,
                'values' => [
                    ['oro.workflow.test_workflow.label', 'test_workflow', null, 'oro.workflow.test_workflow.label'],
                    [
                        'oro.workflow.test_workflow.transition.transition1.label',
                        'test_workflow',
                        null,
                        'oro.workflow.test_workflow.transition.transition1.label'
                    ],
                    [
                        'oro.workflow.test_workflow.transition.transition1.warning_message',
                        'test_workflow',
                        null,
                        'oro.workflow.test_workflow.transition.transition1.warning_message'
                    ],
                    [
                        'oro.workflow.test_workflow.step.step1.label',
                        'test_workflow',
                        null,
                        'oro.workflow.test_workflow.step.step1.label'
                    ],
                    [
                        'oro.workflow.test_workflow.transition.transition1.attribute.attribute1.label',
                        'test_workflow',
                        null,
                        'oro.workflow.test_workflow.transition.transition1.attribute.attribute1.label'
                    ],
                    [
                        'oro.workflow.test_workflow.attribute.attribute1.label',
                        'test_workflow',
                        null,
                        'oro.workflow.test_workflow.attribute.attribute1.label'
                    ],
                    [
                        'oro.workflow.test_workflow.attribute.attribute2.label',
                        'test_workflow',
                        null,
                        ''
                    ],
                ],
                'useKeyAsTranslation' => true,
            ]
        ];
    }
}
