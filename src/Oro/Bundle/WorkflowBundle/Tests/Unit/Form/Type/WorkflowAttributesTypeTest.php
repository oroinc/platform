<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ActionBundle\Model\Attribute;
use Oro\Bundle\ActionBundle\Model\AttributeGuesser;
use Oro\Bundle\SecurityBundle\Util\PropertyPathSecurityHelper;
use Oro\Bundle\WorkflowBundle\Form\EventListener\DefaultValuesListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\FormInitListener;
use Oro\Bundle\WorkflowBundle\Form\EventListener\RequiredAttributesListener;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowAttributesType;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Model\WorkflowRegistry;
use Oro\Component\Action\Action\ActionInterface;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\Exception\InvalidConfigurationException;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Contracts\Translation\TranslatorInterface;

class WorkflowAttributesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /** @var WorkflowRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $workflowRegistry;

    /** @var AttributeGuesser|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeGuesser;

    /** @var DefaultValuesListener|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultValuesListener;

    /** @var FormInitListener|\PHPUnit\Framework\MockObject\MockObject */
    private $formInitListener;

    /** @var RequiredAttributesListener|\PHPUnit\Framework\MockObject\MockObject */
    private $requiredAttributesListener;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var PropertyPathSecurityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyPathSecurityHelper;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var WorkflowAttributesType */
    private $type;

    protected function setUp(): void
    {
        $this->workflowRegistry = $this->createMock(WorkflowRegistry::class);
        $this->attributeGuesser = $this->createMock(AttributeGuesser::class);
        $this->defaultValuesListener = $this->getMockBuilder(DefaultValuesListener::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'setDefaultValues'])
            ->getMock();
        $this->formInitListener = $this->getMockBuilder(FormInitListener::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'executeInitAction'])
            ->getMock();
        $this->requiredAttributesListener = $this->getMockBuilder(RequiredAttributesListener::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['initialize', 'onPreSetData', 'onSubmit'])
            ->getMock();
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->propertyPathSecurityHelper = $this->createMock(PropertyPathSecurityHelper::class);
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
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    /**
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
            ->willReturnCallback(function ($id, $parameters, $domain) {
                return $domain === 'custom' ? $id : sprintf('%s-%s', $id, $domain);
            });

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with('attr', TextType::class, $expectedOptions)
            ->willReturnSelf();

        $this->type->buildForm($builder, $formOptions);
    }

    public function buildFormProvider(): array
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
                    $formOptions['attribute_default_values'] ?? []
                );
        } else {
            $this->defaultValuesListener->expects($this->never())
                ->method($this->anything());
        }

        // Check init action listener is subscribed or not subscribed
        if (!empty($formOptions['form_init'])) {
            $this->formInitListener->expects($this->once())
                ->method('initialize')
                ->with($formOptions['workflow_item'], $formOptions['form_init']);
        } else {
            $this->formInitListener->expects($this->never())
                ->method($this->anything());
        }

        // Check required attributes listener is subscribed or not subscribed
        if (!empty($formOptions['attribute_fields'])) {
            $this->requiredAttributesListener->expects($this->once())
                ->method('initialize')
                ->with(array_keys($formOptions['attribute_fields']));
            $this->requiredAttributesListener->expects($this->once())
                ->method('onPreSetData')
                ->with($this->isInstanceOf(FormEvent::class));
            $this->requiredAttributesListener->expects($this->once())
                ->method('onSubmit')
                ->with($this->isInstanceOf(FormEvent::class));
        } else {
            $this->requiredAttributesListener->expects($this->never())
                ->method($this->anything());
        }

        // Set guessed data for attributes
        $guessClassAttributeFormExpectations = [];
        $guessClassAttributeFormExpectationResults = [];
        foreach ($guessedData as $guess) {
            $guessClassAttributeFormExpectations[] = [$guess['entity'], $this->isInstanceOf(Attribute::class)];
            $guessClassAttributeFormExpectationResults[] = new TypeGuess(
                $guess['form_type'],
                $guess['form_options'],
                TypeGuess::VERY_HIGH_CONFIDENCE
            );
        }
        $this->attributeGuesser->expects($this->exactly(count($guessClassAttributeFormExpectations)))
            ->method('guessClassAttributeForm')
            ->withConsecutive(...$guessClassAttributeFormExpectations)
            ->willReturnOnConsecutiveCalls(...$guessClassAttributeFormExpectationResults);

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
     */
    public function submitDataProvider(): array
    {
        return [
            'empty_attribute_fields' => [
                'submitData' => [],
                'formData' => $this->createWorkflowData(),
                'formOptions' => [
                    'workflow_item' => $this->createWorkflowItem($workflow = $this->createWorkflow('test_workflow')),
                    'workflow' => $workflow,
                    'attribute_fields' => []
                ],
                'childrenOptions' => []
            ],
            'existing_data' => [
                'submitData' => ['first' => 'first_string', 'second' => 'second_string'],
                'formData' => $this->createWorkflowData(
                    [
                        'first' => 'first_string',
                        'second' => 'second_string',
                    ]
                ),
                'formOptions' => [
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        [
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        ]
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'first'  => [
                            'form_type' => TextType::class,
                            'label' => 'First Custom',
                            'options' => ['required' => true]
                        ],
                        'second' => [
                            'form_type' => TextType::class,
                            'options' => ['required' => false, 'label' => 'Second Custom']
                        ],
                    ],
                    'attribute_default_values' => ['first' => 'Test'],
                    'form_init' => $this->createMock(ActionInterface::class)
                ],
                'childrenOptions' => [
                    'first'  => ['label' => 'First Custom', 'required' => true],
                    'second' => ['label' => 'Second Custom', 'required' => false],
                ]
            ],
            'partial_fields' => [
                'submitData' => ['first' => 'first_string_modified'],
                'formData' => $this->createWorkflowData(
                    [
                        'first' => 'first_string_modified',
                        'second' => 'second_string',
                    ]
                ),
                'formOptions' => [
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_partial_attributes',
                        [
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        ]
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'first'  => [
                            'form_type' => TextType::class,
                            'label' => 'First Custom',
                            'options' => ['required' => true]
                        ],
                    ]
                ],
                'childrenOptions' => [
                    'first'  => ['label' => 'First Custom', 'required' => true],
                ],
                'guessedData' => [],
                'sourceWorkflowData' => $this->createWorkflowData(
                    [
                        'first' => 'first_string',
                        'second' => 'second_string',
                    ]
                )
            ],
            'disable_fields' => [
                'submitData' => ['first' => 'first_string', 'second' => 'second_string'],
                'formData' => $this->createWorkflowData(),
                'formOptions' => [
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        [
                            'first' => $this->createAttribute('first', 'string', 'First'),
                            'second' => $this->createAttribute('second', 'string', 'Second'),
                        ]
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'first'  => ['form_type' => TextType::class, 'options' => ['required' => true]],
                        'second' => ['form_type' => TextType::class, 'options' => ['required' => false]],
                    ],
                    'disable_attribute_fields' => true
                ],
                'childrenOptions' => [
                    'first'  => ['label' => 'First', 'required' => true, 'disabled' => true],
                    'second' => ['label' => 'Second', 'required' => false, 'disabled' => true],
                ]
            ],
            'guessed_fields' => [
                'submitData' => ['first' => 'first_string'],
                'formData' => $this->createWorkflowData(['first' => 'first_string']),
                'formOptions' => [
                    'workflow' => $workflow = $this->createWorkflow(
                        'test_workflow_with_attributes',
                        ['first' => $this->createAttribute('first', null, 'Attribute Label', 'entity.first')],
                        [],
                        'RelatedEntity'
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'first' => null
                    ],
                ],
                'childrenOptions' => [
                    'first'  => [
                        'label' => 'Guessed Label',
                        'required' => false,
                        'attr' => [
                            'maxlength' => 50
                        ]
                    ],
                ],
                'guessedData' => [
                    [
                        'entity' => 'RelatedEntity',
                        'form_type' => TextType::class,
                        'form_options' => [
                            'label' => 'Guessed Label',
                            'attr' => [
                                'maxlength' => 50
                            ]
                        ]
                    ]
                ],
            ]
        ];
    }

    /**
     * @dataProvider submitWithExceptionDataProvider
     */
    public function testSubmitWithException(
        string $expectedException,
        string $expectedMessage,
        array $options
    ) {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $form = $this->factory->create(WorkflowAttributesType::class, null, $options);
        $form->submit([]);
    }

    public function submitWithExceptionDataProvider(): array
    {
        return [
            'no_workflow_item' => [
                'expectedException' => MissingOptionsException::class,
                'expectedMessage' =>
                    'The required option "workflow_item" is missing.',
                'options' => [],
            ],
            'unknown_workflow_attribute' => [
                'expectedException' => InvalidConfigurationException::class,
                'expectedMessage' => 'Invalid reference to unknown attribute "first" of workflow "test_workflow".',
                'options' => [
                    'workflow' => $workflow = $this->createWorkflow('test_workflow'),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'first'  => ['form_type' => TextType::class, 'options' => ['required' => true]]
                    ],
                ],
            ],
            'form_type_is_not_defined' => [
                'expectedException' => InvalidConfigurationException::class,
                'expectedMessage' =>
                    'Parameter "form_type" must be defined for attribute "test" in workflow "test_workflow".',
                'options' => [
                    'workflow' => $this->createWorkflow(
                        'test_workflow',
                        [
                            'test' => $this->createAttribute('test', 'string', 'Test')
                        ]
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'test'  => []
                    ],
                ]
            ],
            'form_type_cant_guessed' => [
                'expectedException' => InvalidConfigurationException::class,
                'expectedMessage' =>
                    'Parameter "form_type" must be defined for attribute "test" in workflow "test_workflow".',
                'options' => [
                    'workflow' => $this->createWorkflow(
                        'test_workflow',
                        [
                            'test' => $this->createAttribute('test', null, null, 'entity.field')
                        ]
                    ),
                    'workflow_item' => $this->createWorkflowItem($workflow),
                    'attribute_fields' => [
                        'test'  => []
                    ],
                ]
            ],
        ];
    }

    public function testNotEditableAttributes()
    {
        $entity = (object)['first' => null, 'second' => null];
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
                'second' => ['form_type' => TextType::class]
            ]
        ];

        $this->propertyPathSecurityHelper->expects($this->exactly(2))
            ->method('isGrantedByPropertyPath')
            ->willReturnMap([
                [$entity, 'first', 'EDIT', true],
                [$entity, 'second', 'EDIT', false]
            ]);

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

        $this->propertyPathSecurityHelper->expects($this->never())
            ->method('isGrantedByPropertyPath');

        $form = $this->factory->create(WorkflowAttributesType::class, $formData, $formOptions);

        $this->assertTrue($form->has('first'));
        $this->assertTrue($form->has('second'));
    }

    public function testNormalizers()
    {
        $expectedWorkflow = $this->createWorkflow('test_workflow');
        $options = [
            'workflow_item' => $this->createWorkflowItem($expectedWorkflow),
            'attribute_fields' => [],
        ];

        $this->workflowRegistry->expects($this->once())
            ->method('getWorkflow')
            ->with($expectedWorkflow->getName())
            ->willReturn($expectedWorkflow);

        $this->factory->create(WorkflowAttributesType::class, null, $options);
    }
}
