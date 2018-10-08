<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Translation\TranslatorInterface;

class WorkflowAttributesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $defaultValuesListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $formInitListener;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $requiredAttributesListener;

    /**
     * @var WorkflowAttributesType
     */
    protected $type;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $workflowRegistry;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $attributeGuesser;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $dispatcher;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject
     */
    protected $propertyPathSecurityHelper;

    /**
     * @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $translator;

    protected function setUp()
    {
        $this->workflowRegistry = $this->createWorkflowRegistryMock();
        $this->attributeGuesser = $this->createAttributeGuesserMock();
        $this->defaultValuesListener = $this->createDefaultValuesListenerMock();
        $this->formInitListener = $this->createFormInitListenerMock();
        $this->requiredAttributesListener = $this->createRequiredAttributesListenerMock();
        $this->dispatcher = $this->createDispatcherMock();
        $this->propertyPathSecurityHelper = $this->createPropertyPathSecurityHelper();
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->type = $this->createWorkflowAttributesType(
            $this->workflowRegistry,
            $this->attributeGuesser,
            $this->defaultValuesListener,
            $this->formInitListener,
            $this->requiredAttributesListener,
            $this->dispatcher,
            $this->propertyPathSecurityHelper,
            $this->translator
        );
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    $this->type
                ],
                []
            ),
        ];
        parent::setUp();
    }

    /**
     * @param array $attributeField
     * @param array $expectedOptions
     *
     * @dataProvider buildFormProvider
     */
    public function testBuildForm(array $attributeField, array $expectedOptions)
    {
        $workflow = $this->createWorkflow(
            'test_workflow_with_attributes',
            [
                'attr' => $this->createAttribute('attr', 'string', 'AttributeLabel'),
            ]
        );

        $formOptions = [
            'workflow' => $workflow,
            'workflow_item' => $this->createWorkflowItem($workflow),
            'disable_attribute_fields' => [],
            'attribute_fields' => [
                'attr'  => $attributeField,
            ],
        ];

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, $parameters, $domain) {
                    return $domain === 'custom' ? $id : sprintf('%s-%s', $id, $domain);
                }
            );

        /* @var $builder FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->at(1))
            ->method('add')
            ->with('attr', TextType::class, $expectedOptions)
            ->willReturnSelf();

        $this->type->buildForm($builder, $formOptions);
    }

    /**
     * @return array
     */
    public function buildFormProvider()
    {
        return [
            'root label' => [
                'attribute' => [
                    'form_type' => TextType::class,
                    'label' => 'RootLabel1',
                ],
                'expected' => [
                    'label' => 'RootLabel1',
                    'required' => false,
                ],
            ],
            'option label' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                    'options' => [
                        'label' => 'OptionsLabel2',
                    ],
                ],
                'expected' => [
                    'label' => 'OptionsLabel2',
                    'required' => false,
                ],
            ],
            'array option label' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                    'options' => [
                        'label' => ['OptionsLabel3'],
                    ],
                ],
                'expected' => [
                    'label' => 'OptionsLabel3',
                    'required' => false,
                ],
            ],
            'no translation' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                    'options' => [
                        'label' => 'OptionsLabel4',
                        'translation_domain' => 'custom',
                    ],
                ],
                'expected' => [
                    'label' => 'AttributeLabel',
                    'required' => false,
                    'translation_domain' => 'custom',
                ],
            ],
            'custom translation domain' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                    'options' => [
                        'label' => 'OptionsLabel4',
                        'translation_domain' => 'messages',
                    ],
                ],
                'expected' => [
                    'label' => 'OptionsLabel4',
                    'required' => false,
                    'translation_domain' => 'messages',
                ],
            ],
            'no label' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                ],
                'expected' => [
                    'label' => 'AttributeLabel',
                    'required' => false,
                ],
            ],
            'workflow label' => [
                'attribute'  => [
                    'form_type' => TextType::class,
                    'label' => 'oro.workflow.attribute6.label',
                ],
                'expected' => [
                    'label' => 'oro.workflow.attribute6.label',
                    'required' => false,
                    'translation_domain' => 'workflows',
                ],
            ]
        ];
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $submitData
     * @param WorkflowData $formData
     * @param array $formOptions
     * @param array $childrenOptions
     * @param array $guessedData
     * @param WorkflowData|null $sourceWorkflowData
     */
    public function testSubmit(
        array $submitData,
        WorkflowData $formData,
        array $formOptions,
        array $childrenOptions,
        array $guessedData = [],
        WorkflowData $sourceWorkflowData = null
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
        if (!empty($formOptions['form_init'])) {
            $this->formInitListener->expects($this->once())
                ->method('initialize')
                ->with(
                    $formOptions['workflow_item'],
                    $formOptions['form_init']
                );
        } else {
            $this->formInitListener->expects($this->never())->method($this->anything());
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

        $form = $this->factory->create(WorkflowAttributesType::class, $sourceWorkflowData, $formOptions);

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
                            'form_type' => TextType::class,
                            'label' => 'First Custom',
                            'options' => array('required' => true)
                        ),
                        'second' => array(
                            'form_type' => TextType::class,
                            'options' => array('required' => false, 'label' => 'Second Custom')
                        ),
                    ),
                    'attribute_default_values' => array('first' => 'Test'),
                    'form_init' => $this->createMock('Oro\Component\Action\Action\ActionInterface')
                ),
                'childrenOptions' => array(
                    'first'  => array('label' => 'First Custom', 'required' => true),
                    'second' => array('label' => 'Second Custom', 'required' => false),
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
                            'form_type' => TextType::class,
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
                        'first'  => array('form_type' => TextType::class, 'options' => array('required' => true)),
                        'second' => array('form_type' => TextType::class, 'options' => array('required' => false)),
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
                        'label' => 'Guessed Label',
                        'required' => false,
                        'attr' => [
                            'maxlength' => 50
                        ]
                    ),
                ),
                'guessedData' => array(
                    array(
                        'entity' => 'RelatedEntity',
                        'form_type' => TextType::class,
                        'form_options' => array(
                            'label' => 'Guessed Label',
                            'attr' => [
                                'maxlength' => 50
                            ]
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
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $form = $this->factory->create(WorkflowAttributesType::class, null, $options);
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
                        'first'  => array('form_type' => TextType::class, 'options' => array('required' => true))
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

    public function testNotEditableAttributes()
    {
        $entity = (object)['first' => null, 'second' => null];
        $formData = $this->createWorkflowData(array('entity' => $entity));
        $workflow = $this->createWorkflow(
            'test_workflow_with_attributes',
            array(
                'first' => $this->createAttribute('first', 'string', 'First'),
                'second' => $this->createAttribute('second', 'string', 'Second'),
            )
        );
        $workflow->getDefinition()->setEntityAttributeName('entity');
        $formOptions = array(
            'workflow' => $workflow,
            'workflow_item' => $this->createWorkflowItem($workflow),
            'attribute_fields' => array(
                'first'  => array('form_type' => TextType::class),
                'second' => array('form_type' => TextType::class)
            )
        );

        $this->propertyPathSecurityHelper->expects($this->at(0))
            ->method('isGrantedByPropertyPath')
            ->with($entity, 'first', 'EDIT')
            ->willReturn(true);
        $this->propertyPathSecurityHelper->expects($this->at(1))
            ->method('isGrantedByPropertyPath')
            ->with($entity, 'second', 'EDIT')
            ->willReturn(false);

        $form = $this->factory->create(WorkflowAttributesType::class, $formData, $formOptions);

        $this->assertTrue($form->has('first'));
        $this->assertFalse($form->has('second'));
    }

    public function testEditableVirtualAttributes()
    {
        $entity = new \stdClass();
        $formData = $this->createWorkflowData(['entity' => $entity]);
        $workflow = $this->createWorkflow(
            'test_workflow_with_attributes',
            [
                'first' => $this->createAttribute('first', 'string', 'First'),
                'second' => $this->createAttribute('second', 'string', 'Second'),
            ]
        );
        $workflow->getDefinition()->setEntityAttributeName('entity');
        $formOptions = [
            'workflow' => $workflow,
            'workflow_item' => $this->createWorkflowItem($workflow),
            'attribute_fields' => [
                'first'  => ['form_type' => TextType::class],
                'second' => ['form_type' => TextType::class],
            ],
        ];

        $this->propertyPathSecurityHelper->expects($this->never())->method('isGrantedByPropertyPath');

        $form = $this->factory->create(WorkflowAttributesType::class, $formData, $formOptions);

        $this->assertTrue($form->has('first'));
        $this->assertTrue($form->has('second'));
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

        $this->factory->create(WorkflowAttributesType::class, null, $options);
    }
}
