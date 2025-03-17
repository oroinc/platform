<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadataInfo;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Form\WorkflowVariableDataTransformer;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;

class WorkflowVariablesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    private VariableGuesser&MockObject $variableGuesser;
    private WorkflowVariablesType $type;

    #[\Override]
    protected function setUp(): void
    {
        $this->variableGuesser = $this->createMock(VariableGuesser::class);

        $classMetadata = $this->createMock(ClassMetadataInfo::class);
        $classMetadata->expects(self::any())
            ->method('getIdentifierFieldNames')
            ->willReturn(['id']);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->type = new WorkflowVariablesType($this->variableGuesser, $doctrine);

        parent::setUp();
    }

    #[\Override]
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->type], [])
        ];
    }

    public function testBuildForm(): void
    {
        $variable = $this->createMock(Variable::class);
        $variable->expects(self::once())
            ->method('getName')
            ->willReturn('variableName');
        $variable->expects(self::once())
            ->method('getType')
            ->willReturn('entity');

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::once())
            ->method('getVariables')
            ->with(true)
            ->willReturn([$variable]);

        $typeGuess = $this->createMock(TypeGuess::class);
        $typeGuess->expects(self::once())
            ->method('getOptions')
            ->willReturn(['label' => 'testLabel']);

        $this->variableGuesser->expects(self::once())
            ->method('guessVariableForm')
            ->with($variable)
            ->willReturn($typeGuess);

        $field = $this->createMock(FormBuilderInterface::class);
        $field->expects(self::once())
            ->method('addModelTransformer')
            ->with($this->isInstanceOf(WorkflowVariableDataTransformer::class));

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::once())
            ->method('create')
            ->with('variableName', EntityType::class, ['label' => 'testLabel'])
            ->willReturn($field);
        $builder->expects(self::once())
            ->method('add')
            ->with($field);

        $this->type->buildForm($builder, ['workflow' => $workflow]);
    }

    /**
     * @dataProvider submitDataProvider
     */
    public function testSubmit(
        array $submitData,
        WorkflowData $formData,
        array $formOptions,
        array $childrenOptions,
        array $guessedData = []
    ): void {
        $guessVariableFormExpectationResults = [];
        foreach ($guessedData as $guess) {
            $guessVariableFormExpectationResults[] = new TypeGuess(
                $guess['form_type'],
                $guess['form_options'],
                TypeGuess::VERY_HIGH_CONFIDENCE
            );
        }
        $this->variableGuesser->expects(self::exactly(count($guessVariableFormExpectationResults)))
            ->method('guessVariableForm')
            ->willReturnOnConsecutiveCalls(...$guessVariableFormExpectationResults);

        $form = $this->factory->create(WorkflowVariablesType::class, null, $formOptions);

        self::assertSameSize($childrenOptions, $form->all());

        foreach ($childrenOptions as $childName => $childOptions) {
            self::assertTrue($form->has($childName));
            $childForm = $form->get($childName);

            if (!is_array($childOptions)) {
                continue;
            }

            foreach ($childOptions as $optionName => $optionValue) {
                self::assertTrue($childForm->getConfig()->hasOption($optionName));
                self::assertEquals($optionValue, $childForm->getConfig()->getOption($optionName));
            }
        }

        $form->submit($submitData);

        self::assertTrue($form->isSynchronized());
        self::assertEquals($formData, $form->getData(), 'Actual form data does not equal expected form data');
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function submitDataProvider(): array
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
                'submitData' => ['first' => ['option11', 'option12']],
                'formData' => $this->createWorkflowData([
                    'first' => ['option11', 'option12'],
                ]),
                'form_options' => [
                    'workflow' => $this->createWorkflowWithVariables([
                        [
                            'name' => 'first',
                            'label' => 'First Label',
                            'value' => ['option11', 'option12'],
                            'type' => 'array'
                        ]
                    ])
                ],
                'childrenOptions' => [
                    'first' => [
                        'label' => 'First Label',
                        'choices' => ['option11' => 'option11', 'option12' => 'option12'],
                        'multiple' => true,
                        'expanded' => true,
                    ],
                ],
                'guessedData' => [
                    [
                        'form_type' => ChoiceType::class,
                        'form_options' => [
                            'label' => 'First Label',
                            'choices' => ['option11' => 'option11', 'option12' => 'option12'],
                            'multiple' => true,
                            'expanded' => true,
                        ]
                    ],
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

    private function createWorkflowWithVariables(array $variables = []): Workflow
    {
        $variableCollection = new ArrayCollection();

        foreach ($variables as $varOptions) {
            $variable = new Variable();
            $variable->setName($varOptions['name']);
            $variable->setType($varOptions['type']);
            $variable->setOptions($varOptions['options'] ?? []);
            $variable->setValue($varOptions['value']);
            $variable->setLabel($varOptions['label']);
            $variableCollection->add($variable);
        }

        $workflow = $this->createMock(Workflow::class);
        $workflow->expects(self::any())
            ->method('getVariables')
            ->willReturn($variableCollection);

        return $workflow;
    }
}
