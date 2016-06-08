<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;

class WorkflowAttributesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $defaultValuesListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $initActionListener;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $requiredAttributesListener;

    /**
     * @var WorkflowAttributesType
     */
    protected $type;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $attributeGuesser;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $dispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->workflowRegistry = $this->createWorkflowRegistryMock();
        $this->attributeGuesser = $this->createAttributeGuesserMock();
        $this->defaultValuesListener = $this->createDefaultValuesListenerMock();
        $this->initActionListener = $this->createInitActionsListenerMock();
        $this->requiredAttributesListener = $this->createRequiredAttributesListenerMock();
        $this->dispatcher = $this->createDispatcherMock();

        $this->type = $this->createWorkflowAttributesType(
            $this->workflowRegistry,
            $this->attributeGuesser,
            $this->defaultValuesListener,
            $this->initActionListener,
            $this->requiredAttributesListener,
            $this->dispatcher
        );
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        $submitData,
        $formData,
        array $formOptions,
        array $childrenOptions,
        array $guessedData = array(),
        $sourceWorkflowData = null
    ) {
        // Check default values listener is subscribed or not subscribed
        if (!empty($formOptions['attribute_default_values'])) {
            $this->defaultValuesListener->expects($this->once())
                ->method('initialize')
                ->with(
                    $formOptions['workflow_item'],
                    isset($formOptions['attribute_default_values']) ? $formOptions['attribute_default_values'] : array()
                );
        } else {
            $this->defaultValuesListener->expects($this->never())->method($this->anything());
        }

        // Check init action listener is subscribed or not subscribed
        if (!empty($formOptions['init_actions'])) {
            $this->initActionListener->expects($this->once())
                ->method('initialize')
                ->with(
                    $formOptions['workflow_item'],
                    $formOptions['init_actions']
                );
        } else {
            $this->initActionListener->expects($this->never())->method($this->anything());
        }

        // Check required attributes listener is subscribed or not subscribed
        if (!empty($formOptions['attribute_fields'])) {
            $this->requiredAttributesListener->expects($this->once())
                ->method('initialize')
                ->with(array_keys($formOptions['attribute_fields']));
            $this->requiredAttributesListener->expects($this->once())
                ->method('onPreSetData')
                ->with($this->isInstanceOf('Symfony\Component\Form\FormEvent'));
            $this->requiredAttributesListener->expects($this->once())
                ->method('onSubmit')
                ->with($this->isInstanceOf('Symfony\Component\Form\FormEvent'));
        } else {
            $this->requiredAttributesListener->expects($this->never())->method($this->anything());
        }

        // Set guessed data for attributes
        foreach ($guessedData as $number => $guess) {
            $typeGuess = new TypeGuess($guess['form_type'], $guess['form_options'], TypeGuess::VERY_HIGH_CONFIDENCE);
            $this->attributeGuesser->expects($this->at($number))
                ->method('guessClassAttributeForm')
                ->with($guess['entity'], $this->isInstanceOf('Oro\Bundle\ActionBundle\Model\Attribute'))
                ->will($this->returnValue($typeGuess));
        }

        $form = $this->factory->create($this->type, $sourceWorkflowData, $formOptions);

        $this->assertSameSize($childrenOptions, $form->all());

        foreach ($childrenOptions as $childName => $childOptions) {
            $this->assertTrue($form->has($childName));
            $childForm = $form->get($childName);
            foreach ($childOptions as $optionName => $optionValue) {
                $this->assertTrue($childForm->getConfig()->hasOption($optionName));
                $this->assertEquals($optionValue, $childForm->getConfig()->getOption($optionName));
            }
        }

        $form->submit($submitData);

        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($formData, $form->getData(), 'Actual form data does not equal expected form data');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @return array
     */
    public function submitDataProvider()
    {
        return array(
            'empty_attribute_fields' => array(
                'submitData' => array(),
                'formData' => $this->createWorkflowData(),
                'formOptions' => array(
                    'workflow_item' => $this->createWorkflowItem($workflow = $this->createWorkflow('test_workflow')),
                    'workflow' => $workflow,
                    'attribute_fields' => array()
                ),
                'childrenOptions' => array()
            ),
            'existing_data' => array(
                'submitData' => array('first' => 'first_string', 'second' => 'second_string'),
                'formData' => $this->createWorkflowData(
                    array(
                        'first' => 'first_string',
                        'second' => 'second_string',
                    )
                ),
                'formOptions' => array(
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        array(
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        )
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'first'  => array(
                            'form_type' => 'text',
                            'label' => 'First Custom',
                            'options' => array('required' => true)
                        ),
                        'second' => array(
                            'form_type' => 'text',
                            'options' => array('required' => false, 'label' => 'Second Custom')
                        ),
                    ),
                    'attribute_default_values' => array('first' => 'Test'),
                    'init_actions' => $this->getMock('Oro\Component\Action\Action\ActionInterface')
                ),
                'childrenOptions' => array(
                    'first'  => array('label' => 'First Custom', 'required' => true),
                    'second' => array('label' => 'Second', 'required' => false),
                )
            ),
            'partial_fields' => array(
                'submitData' => array('first' => 'first_string_modified'),
                'formData' => $this->createWorkflowData(
                    array(
                        'first' => 'first_string_modified',
                        'second' => 'second_string',
                    )
                ),
                'formOptions' => array(
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_partial_attributes',
                        array(
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        )
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'first'  => array(
                            'form_type' => 'text',
                            'label' => 'First Custom',
                            'options' => array('required' => true)
                        ),
                    )
                ),
                'childrenOptions' => array(
                    'first'  => array('label' => 'First Custom', 'required' => true),
                ),
                'guessedData' => array(),
                'sourceWorkflowData' => $this->createWorkflowData(
                    array(
                        'first' => 'first_string',
                        'second' => 'second_string',
                    )
                )
            ),
            'disable_fields' => array(
                'submitData' => array('first' => 'first_string', 'second' => 'second_string'),
                'formData' => $this->createWorkflowData(),
                'formOptions' => array(
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        array(
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        )
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'first'  => array('form_type' => 'text', 'options' => array('required' => true)),
                        'second' => array('form_type' => 'text', 'options' => array('required' => false)),
                    ),
                    'disable_attribute_fields' => true
                ),
                'childrenOptions' => array(
                    'first'  => array('label' => 'First', 'required' => true, 'disabled' => true),
                    'second' => array('label' => 'Second', 'required' => false, 'disabled' => true),
                )
            ),
            'guessed_fields' => array(
                'submitData' => array('first' => 'first_string'),
                'formData' => $this->createWorkflowData(array('first' => 'first_string')),
                'formOptions' => array(
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        array('first' => $this->createAttribute('first', null, 'Attribute Label', 'entity.first')),
                        array(),
                        'RelatedEntity'
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'first' => null
                    ),
                ),
                'childrenOptions' => array(
                    'first'  => array(
                        'label' => 'Attribute Label',
                        'max_length' => 50,
                        'required' => false
                    ),
                ),
                'guessedData' => array(
                    array(
                        'entity' => 'RelatedEntity',
                        'form_type' => 'text',
                        'form_options' => array(
                            'label' => 'Guessed Label',
                            'max_length' => 50,
                        )
                    )
                ),
            )
        );
    }

    /**
     * @dataProvider submitWithExceptionDataProvider
     */
    public function testSubmitWithException(
        $expectedException,
        $expectedMessage,
        array $options
    ) {
        $this->setExpectedException($expectedException, $expectedMessage);

        $form = $this->factory->create($this->type, null, $options);
        $form->submit(array());
    }

    /**
     * @return array
     */
    public function submitWithExceptionDataProvider()
    {
        return array(
            'no_workflow_item' => array(
                'expectedException' => 'Symfony\Component\OptionsResolver\Exception\MissingOptionsException',
                'expectedMessage' =>
                    'The required option "workflow_item" is missing.',
                'options' => array(),
            ),
            'unknown_workflow_attribute' => array(
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedMessage' => 'Invalid reference to unknown attribute "first" of workflow "test_workflow".',
                'options' => array(
                    'workflow' => $workflow = $this->createWorkflow('test_workflow'),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'first'  => array('form_type' => 'text', 'options' => array('required' => true))
                    ),
                ),
            ),
            'form_type_is_not_defined' => array(
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedMessage' =>
                    'Parameter "form_type" must be defined for attribute "test" in workflow "test_workflow".',
                'options' => array(
                    'workflow' => $this->createWorkflow(
                        'test_workflow',
                        array(
                            'test' => $this->createAttribute('test', 'string', 'Test')
                        )
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'test'  => array()
                    ),
                )
            ),
            'form_type_cant_guessed' => array(
                'expectedException' => 'Symfony\Component\Form\Exception\InvalidConfigurationException',
                'expectedMessage' =>
                    'Parameter "form_type" must be defined for attribute "test" in workflow "test_workflow".',
                'options' => array(
                    'workflow' => $this->createWorkflow(
                        'test_workflow',
                        array(
                            'test' => $this->createAttribute('test', null, null, 'entity.field')
                        )
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => array(
                        'test'  => array()
                    ),
                )
            ),
        );
    }

    public function testNormalizers()
    {
        $expectedWorkflow = $this->createWorkflow('test_workflow');
        $options = array(
            'workflow_item' => $this->createWorkflowItem($expectedWorkflow),
            'attribute_fields' => array(),
        );

        $this->workflowRegistry->expects($this->once())->method('getWorkflow')
            ->with($expectedWorkflow->getName())->will($this->returnValue($expectedWorkflow));

        $this->factory->create($this->type, null, $options);
    }

    protected function setFormTypeGuesser(array $guessedData)
    {

    }
}
