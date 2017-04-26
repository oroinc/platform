<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Guess\TypeGuess;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\WorkflowBundle\Acl\AclManager;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Form\Type\WorkflowVariablesType;
use Oro\Bundle\WorkflowBundle\Model\VariableAssembler;
use Oro\Bundle\WorkflowBundle\Model\VariableManager;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Bundle\WorkflowBundle\Restriction\RestrictionManager;

class WorkflowVariablesTypeTest extends AbstractWorkflowAttributesTypeTestCase
{
    /**
     * @var WorkflowVariablesType
     */
    protected $type;

    /**
     * @var VariableGuesser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $variableGuesser;

    protected function setUp()
    {
        parent::setUp();

        $this->variableGuesser = $this->createMock(VariableGuesser::class);

        $this->type = new WorkflowVariablesType($this->variableGuesser);
    }

    public function testBuildForm()
    {
        $workflow = $this->createMock(Workflow::class);
        $variable = $this->createMock(Variable::class);
        $workflow->expects($this->once())
            ->method('getVariables')
            ->with(true)
            ->will($this->returnValue([$variable]));
        $variable->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('variableName'));
        $typeGuess = $this->createMock(TypeGuess::class);
        $this->variableGuesser->expects($this->once())
            ->method('guessVariableForm')
            ->with($variable)
            ->will($this->returnValue($typeGuess));
        $typeGuess->expects($this->once())
            ->method('getType')
            ->will($this->returnValue(TextType::class));
        $typeGuess->expects($this->once())
            ->method('getOptions')
            ->will($this->returnValue(['label' => 'testLabel']));
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->once())
            ->method('add')
            ->with('variableName', TextType::class, ['label' => 'testLabel']);

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
            $this->variableGuesser->expects($this->at($number))
                ->method('guessVariableForm')
                ->will($this->returnValue($typeGuess));
        }

        $form = $this->factory->create($this->type, null, $formOptions);

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
            $var = new Variable();
            $var->setValue($varOptions['value']);
            $var->setType($varOptions['type']);
            $var->setName($varOptions['name']);
            $var->setLabel($varOptions['label']);
            $variableCollection->add($var);
        }

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $aclManager = $this->createMock(AclManager::class);
        $restrictionManager = $this->createMock(RestrictionManager::class);
        /** @var VariableAssembler|\PHPUnit_Framework_MockObject_MockObject $variableAssembler */
        $variableAssembler = $this->getMockBuilder(VariableAssembler::class)
            ->setMethods(['assemble'])
            ->disableOriginalConstructor()
            ->getMock();
        $variableAssembler->expects($this->any())
            ->method('assemble')
            ->willReturn($variableCollection);

        /** @var VariableManager|\PHPUnit_Framework_MockObject_MockObject $variableManager */
        $variableManager = $this->getMockBuilder(VariableManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $variableManager->expects($this->any())
            ->method('getVariableAssembler')
            ->willReturn($variableAssembler);

        $workflow = new Workflow($doctrineHelper, $aclManager, $restrictionManager, null, null, null, $variableManager);

        $definition = new WorkflowDefinition();
        $workflow->setDefinition($definition);

        return $workflow;
    }
}
