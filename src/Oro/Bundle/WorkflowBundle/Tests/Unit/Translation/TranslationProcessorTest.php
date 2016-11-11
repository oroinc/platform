<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Translation;

use Oro\Bundle\WorkflowBundle\Configuration\Handler\ConfigurationHandlerInterface;
use Oro\Bundle\WorkflowBundle\Configuration\WorkflowDefinitionBuilderExtensionInterface;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowStep;
use Oro\Bundle\WorkflowBundle\Helper\WorkflowTranslationHelper;
use Oro\Bundle\WorkflowBundle\Translation\TranslationProcessor;

class TranslationProcessorTest extends \PHPUnit_Framework_TestCase
{
    /** @var TranslationProcessor */
    private $processor;

    /** @var WorkflowTranslationHelper|\PHPUnit_Framework_MockObject_MockObject */
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
        $configuration = ['name' => 'test_workflow', null, 'label' => 'wflabel'];

        $this->translationHelper->expects($this->once())
            ->method('saveTranslation')
            ->with('oro.workflow.test_workflow.label', 'wflabel');

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
     * @param WorkflowDefinition $definition
     * @param array $values
     * @param WorkflowDefinition $expected
     */
    public function testTranslateWorkflowDefinitionFields(
        WorkflowDefinition $definition,
        array $values,
        WorkflowDefinition $expected
    ) {
        $this->translationHelper->expects($this->any())->method('findWorkflowTranslation')->willReturnMap($values);

        $this->processor->translateWorkflowDefinitionFields($definition);

        $this->assertEquals($expected, $definition);
    }

    /**
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

        return [
            'full case' => [
                $definition,
                [
                    ['stored_label_key1', 'test_workflow', null, 'translated_label_key'],
                    ['step1_stored_label_key', 'test_workflow', null, 'translated_step1_stored_label_key'],
                    ['step2_stored_label_key', 'test_workflow', null, 'translated_step2_stored_label_key'],
                    ['transition1_stored_label_key', 'test_workflow', null, 'translated_transition1_stored_label_key'],
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
                $expected
            ]
        ];
    }
}
