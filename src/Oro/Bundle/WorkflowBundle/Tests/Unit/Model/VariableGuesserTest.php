<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFieldsConstraint;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

class VariableGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var VariableGuesser|\PHPUnit\Framework\MockObject\MockObject */
    protected $guesser;

    /** @var Variable|\PHPUnit\Framework\MockObject\MockObject */
    protected $variable;

    /** @var  ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $formConfigProvider;

    /**
     * Test setup.
     */
    protected function setUp()
    {
        $formRegistry = $this->createMock(FormRegistry::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                function ($id, array $parameters, $domain) {
                    $this->assertInternalType('string', $id);
                    $this->assertEquals([], $parameters);
                    $this->assertEquals('workflows', $domain);

                    return $id . '_translated';
                }
            );

        $this->guesser = new VariableGuesser(
            $formRegistry,
            $managerRegistry,
            $entityConfigProvider,
            $this->formConfigProvider,
            $translator
        );
        $this->guesser->addFormTypeMapping('string', 'Symfony\Component\Form\Extension\Core\Type\TextType');

        $this->variable = $this->createMock(Variable::class);
    }

    /**
     * Test variable form guessing.
     *
     * @dataProvider guessVariableFormDataProvider
     *
     * @param TypeGuess $expected
     * @param Variable  $variable
     * @param array     $formMapping
     * @param array     $formConfig
     */
    public function testGuessVariableForm(TypeGuess $expected, Variable $variable, $formMapping = [], $formConfig = [])
    {
        foreach ($formMapping as $mapping) {
            $this->guesser->addFormTypeMapping(
                $mapping['variableType'],
                $mapping['formType'],
                $mapping['formOptions']
            );
        }

        if ($formConfig) {
            $formConfigId = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId')
                ->disableOriginalConstructor()
                ->getMock();
            $formConfigObject = new Config($formConfigId, $formConfig);
            $this->formConfigProvider->expects($this->once())->method('hasConfig')
                ->with($formConfig['entity'])->will($this->returnValue(true));
            $this->formConfigProvider->expects($this->once())->method('getConfig')
                ->with($formConfig['entity'])->will($this->returnValue($formConfigObject));
        }

        $this->assertEquals($expected, $this->guesser->guessVariableForm($variable));
    }

    /**
     * @return array
     */
    public function guessVariableFormDataProvider()
    {
        return [
            'scalar guess' => [ // test guessing scalar variables
                'expected' => new TypeGuess(
                    'formTestType',
                    [
                        'formOption' => 'optionValue',
                        'label' => 'testLabel_translated',
                        'data' => 'testValue',
                    ],
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'variable' => $this->createVariable(
                    'testType',
                    null,
                    ['formOption' => 'optionValue'],
                    'testValue',
                    'testLabel'
                ),
                'formMapping' => [
                    [
                        'variableType' => 'testType',
                        'formType' => 'formTestType',
                        'formOptions' => [
                            'formOption' => 'optionValue'
                        ]
                    ]
                ],
            ],
            'configured entity guess' => [ // test guessing for entities that have a form type configured
                'expected' => new TypeGuess(
                    'test_type',
                    [
                        'key' => 'value',
                        'constraints' => [
                            new NotBlank(),
                            new UserAuthenticationFieldsConstraint(),
                            new GreaterThan(10)
                        ],
                        'tooltip' => 'test_tooltip_translated'
                    ],
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'variable' => $this->createVariable('entity', null, [
                    'class' => 'TestEntity',
                    'form_options' => [
                        'constraints' => [
                            'NotBlank' => null,
                            UserAuthenticationFieldsConstraint::class => null,
                            'GreaterThan' => 10,
                        ],
                        'tooltip' => 'test_tooltip'
                    ]
                ]),
                'formMapping' => [],
                'formConfig' => [
                    'entity' => 'TestEntity',
                    'form_type' => 'test_type',
                    'form_options' => ['key' => 'value']
                ],
            ],
            'entity guess' => [ // test guessing for entities that have defined their form type in the variable config
                'expected' => new TypeGuess(
                    'defined_type',
                    [],
                    TypeGuess::VERY_HIGH_CONFIDENCE
                ),
                'variable' => $this->createVariable('entity', null, [
                    'form_type' => 'defined_type',
                    'class' => 'TestEntity'
                ]),
            ],
        ];
    }

    /**
     * Test guessing with missing type
     */
    public function testGuessWithMissingType()
    {
        $this->variable->expects($this->once())
            ->method('getType')
            ->will($this->returnValue('testType'));

        $typeGuess = $this->guesser->guessVariableForm($this->variable);

        $this->assertNull($typeGuess);
    }

    /**
     * Test guessing without options
     *
     * @dataProvider optionsDataProvider
     * @param string $type
     * @param string $property
     * @param mixed $value
     */
    public function testGuessWithoutOptions($type, $property, $value)
    {
        $this->variable->expects($this->once())
            ->method('getType')
            ->willReturn($type);

        $getter = 'get' . ucfirst($property);
        $this->variable->expects($this->any())
            ->method($getter)
            ->willReturn($value);

        $this->variable->expects($this->once())
            ->method('getFormOptions')
            ->willReturn([]);

        $typeGuess = $this->guesser->guessVariableForm($this->variable);
        $options = $typeGuess->getOptions();

        if (null === $value) {
            $this->assertEmpty($options);
        } else {
            if ($property === 'label') {
                $value .= '_translated';
                $formOption = $property;
            } else {
                $formOption = 'data';
            }

            $this->assertEquals($value, $options[$formOption]);
        }
    }

    /**
     * @return array
     */
    public function optionsDataProvider()
    {
        return [
            ['string', 'label', 'test_label'],
            ['string', 'value', 'test_value'],
            ['string', 'label', null],
            ['string', 'value', null]
        ];
    }

    /**
     * @param string $type
     * @param string $propertyPath
     * @param array  $options
     * @param string   $value
     * @param string   $label
     *
     * @return Variable
     */
    protected function createVariable($type, $propertyPath = null, array $options = [], $value = null, $label = null)
    {
        $variable = new Variable();
        $variable->setType($type)
            ->setPropertyPath($propertyPath)
            ->setOptions($options)
            ->setValue($value)
            ->setLabel($label);

        return $variable;
    }
}
