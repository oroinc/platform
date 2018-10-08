<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Form\WorkflowVariableDataTransformer;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;

class WorkflowVariablesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    use EntityTrait;

    /** @var VariableGuesser|\PHPUnit\Framework\MockObject\MockObject */
    protected $variableGuesser;

    /** @var WorkflowVariablesType */
    protected $type;

    protected function setUp()
    {
        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects($this->any())->method('getIdentifierFieldNames')->willReturn(['id']);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects($this->any())->method('getClassMetadata')->willReturn($classMetadata);

        /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject $managerRegistry */
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $managerRegistry->expects($this->any())->method('getManagerForClass')->willReturn($entityManager);

        $this->variableGuesser = $this->createMock(VariableGuesser::class);

        $this->type = new WorkflowVariablesType($this->variableGuesser, $managerRegistry);
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
    }

    public function testBuildForm()
    {
        $variable = $this->createMock(Variable::class);
        $variable->expects($this->once())->method('getName')->willReturn('variableName');
        $variable->expects($this->once())->method('getType')->willReturn('entity');

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->once())->method('getVariables')->with(true)->willReturn([$variable]);

        $typeGuess = $this->createMock(TypeGuess::class);
        $typeGuess->expects($this->once())->method('getOptions')->willReturn(['label' => 'testLabel']);

        $this->variableGuesser->expects($this->once())
            ->method('guessVariableForm')
            ->with($variable)
            ->willReturn($typeGuess);

        $field = $this->createMock(FormBuilderInterface::class);
        $field->expects($this->once())
            ->method('addModelTransformer')
            ->with($this->isInstanceOf(WorkflowVariableDataTransformer::class));

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('create')
            ->with('variableName', EntityType::class, ['label' => 'testLabel'])
            ->willReturn($field);
        $builder->expects($this->once())->method('add')->with($field);

        $this->type->buildForm($builder, ['workflow' => $workflow]);
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array             $submitData
     * @param WorkflowData      $formData
     * @param array             $formOptions
     * @param array             $childrenOptions
     * @param array             $guessedData
     */
    public function testSubmit(
        array $submitData,
        WorkflowData $formData,
        array $formOptions,
        array $childrenOptions,
        array $guessedData = []
    ) {
        foreach ($guessedData as $number => $guess) {
            $typeGuess = new TypeGuess($guess['form_type'], $guess['form_options'], TypeGuess::VERY_HIGH_CONFIDENCE);

            $this->variableGuesser->expects($this->at($number))->method('guessVariableForm')->willReturn($typeGuess);
        }

        $form = $this->factory->create(WorkflowVariablesType::class, null, $formOptions);

        $this->assertSameSize($childrenOptions, $form->all());

        foreach ($childrenOptions as $childName => $childOptions) {
            $this->assertTrue($form->has($childName));
            $childForm = $form->get($childName);

            if (!is_array($childOptions)) {
                continue;
            }

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
        return [
            'empty_variables' => [
                'submitData' => [],
                'formData' => $this->createWorkflowData(),
                'formOptions' => [
                    'workflow' => $this->createWorkflowWithVariables([]),
                ],
                'childrenOptions' => []
            ],
            'string_variables' => [
                'submitData' => ['first' => 'first_string', 'second' => 'second_string'],
                'formData' => $this->createWorkflowData(['first' => 'first_string', 'second' => 'second_string']),
                'form_options' => [
                    'workflow' => $this->createWorkflowWithVariables([
                        [
                            'name' => 'first',
                            'label' => 'First Label',
                            'value' => 'first_string',
                            'type' => 'string'
                        ],
                        [
                            'name' => 'second',
                            'label' => 'Second Label',
                            'value' => 'second_string',
                            'type' => 'string'
                        ]
                    ])
                ],
                'childrenOptions' => ['first' => [], 'second' => []],
                'guessedData' => [
                    [
                        'form_type' => TextType::class,
                        'form_options' => []
                    ],
                    [
                        'form_type' => TextType::class,
                        'form_options' => []
                    ]
                ]
            ],
            'array_variables' => [
                'submitData' => ['first' => ['element11', 'element12' ], 'second' => ['element21', 'element22']],
                'formData' => $this->createWorkflowData([
                    'first' => ['element11', 'element12'],
                    'second' => ['element21', 'element22']
                ]),
                'form_options' => [
                    'workflow' => $this->createWorkflowWithVariables([
                        [
                            'name' => 'first',
                            'label' => 'First Label',
                            'value' => ['element11', 'element12'],
                            'type' => 'array'
                        ],
                        [
                            'name' => 'second',
                            'label' => 'Second Label',
                            'value' => ['element21', 'element22'],
                            'type' => 'array'
                        ]
                    ])
                ],
                'childrenOptions' => [
                    'first' => ['label' => 'First Label'],
                    'second' => ['label' => 'Second Label']
                ],
                'guessedData' => [
                    [
                        'form_type' => TextType::class,
                        'form_options' => ['label' => 'First Label']
                    ],
                    [
                        'form_type' => TextType::class,
                        'form_options' => ['label' => 'Second Label']
                    ]
                ]
            ],
            'entity_variable_without_identifier' => [
                'submitData' => ['first' => (object) ['id' => 1]],
                'formData' => $this->createWorkflowData(['first' => 1]),
                'form_options' => [
                    'workflow' => $this->createWorkflowWithVariables([
                        [
                            'name' => 'first',
                            'label' => 'First Label',
                            'value' => 1,
                            'type' => 'entity',
                            'options' => [
                                'class' => 'stdClass'
                            ]
                        ]
                    ])
                ],
                'childrenOptions' => ['first' => []],
                'guessedData' => [
                    [
                        'form_type' => TextType::class,
                        'form_options' => []
                    ]
                ]
            ],
            'entity_variable_with_identifier' => [
                'submitData' => ['first' => (object) ['id' => 1]],
                'formData' => $this->createWorkflowData(['first' => 1]),
                'form_options' => [
                    'workflow' => $this->createWorkflowWithVariables([
                        [
                            'name' => 'first',
                            'label' => 'First Label',
                            'value' => 1,
                            'type' => 'entity',
                            'options' => [
                                'class' => 'stdClass',
                                'identifier' => 'id'
                            ]
                        ]
                    ])
                ],
                'childrenOptions' => ['first' => []],
                'guessedData' => [
                    [
                        'form_type' => TextType::class,
                        'form_options' => []
                    ]
                ]
            ],
        ];
    }

    /**
     * @param array $variables
     * @return Workflow
     */
    protected function createWorkflowWithVariables(array $variables = [])
    {
        $variableCollection = new ArrayCollection();

        foreach ($variables as $key => $varOptions) {
            $variableCollection->add(
                $this->getEntity(
                    Variable::class,
                    [
                        'value' => $varOptions['value'],
                        'type' => $varOptions['type'],
                        'name' => $varOptions['name'],
                        'label' => $varOptions['label'],
                        'options' => $varOptions['options'] ?? []
                    ]
                )
            );
        }

        /** @var Workflow|\PHPUnit\Framework\MockObject\MockObject $workflow */
        $workflow = $this->createMock(Workflow::class);
        $workflow->expects($this->any())->method('getVariables')->willReturn($variableCollection);

        return $workflow;
    }
}
