<?php

namespace Oro\Bundle\WorkflowBundle\Tests\Unit\Model;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\UserBundle\Validator\Constraints\UserAuthenticationFields;
use Oro\Bundle\WorkflowBundle\Model\Variable;
use Oro\Bundle\WorkflowBundle\Model\VariableGuesser;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormRegistry;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Contracts\Translation\TranslatorInterface;

class VariableGuesserTest extends \PHPUnit\Framework\TestCase
{
    /** @var Variable|\PHPUnit\Framework\MockObject\MockObject */
    private $variable;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $formConfigProvider;

    /** @var VariableGuesser */
    private $guesser;

    protected function setUp(): void
    {
        $formRegistry = $this->createMock(FormRegistry::class);
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $entityConfigProvider = $this->createMock(ConfigProvider::class);
        $this->formConfigProvider = $this->createMock(ConfigProvider::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $parameters, $domain) {
                $this->assertIsString($id);
                $this->assertEquals([], $parameters);
                $this->assertEquals('workflows', $domain);

                return $id . '_translated';
            });

        $this->guesser = new VariableGuesser(
            $formRegistry,
            $managerRegistry,
            $entityConfigProvider,
            $this->formConfigProvider,
            $translator
        );
        $this->guesser->addFormTypeMapping('string', TextType::class);

        $this->variable = $this->createMock(Variable::class);
    }

    /**
     * Test variable form guessing.
     *
     * @dataProvider guessVariableFormDataProvider
     */
    public function testGuessVariableForm(
        TypeGuess $expected,
        Variable $variable,
        array $formMapping = [],
        array $formConfig = []
    ) {
        foreach ($formMapping as $mapping) {
            $this->guesser->addFormTypeMapping(
                $mapping['variableType'],
                $mapping['formType'],
                $mapping['formOptions']
            );
        }

        if ($formConfig) {
            $formConfigId = $this->createMock(FieldConfigId::class);
            $formConfigObject = new Config($formConfigId, $formConfig);
            $this->formConfigProvider->expects($this->once())
                ->method('hasConfig')
                ->with($formConfig['entity'])
                ->willReturn(true);
            $this->formConfigProvider->expects($this->once())
                ->method('getConfig')
                ->with($formConfig['entity'])
                ->willReturn($formConfigObject);
        }

        $this->assertEquals($expected, $this->guesser->guessVariableForm($variable));
    }

    public function guessVariableFormDataProvider(): array
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
                            new UserAuthenticationFields(),
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
                            UserAuthenticationFields::class => null,
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
            ->willReturn('testType');

        $typeGuess = $this->guesser->guessVariableForm($this->variable);

        $this->assertNull($typeGuess);
    }

    /**
     * Test guessing without options
     *
     * @dataProvider optionsDataProvider
     */
    public function testGuessWithoutOptions(string $type, string $property, mixed $value)
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

    public function optionsDataProvider(): array
    {
        return [
            ['string', 'label', 'test_label'],
            ['string', 'value', 'test_value'],
            ['string', 'label', null],
            ['string', 'value', null]
        ];
    }

    private function createVariable(
        string $type,
        string $propertyPath = null,
        array $options = [],
        string $value = null,
        string $label = null
    ): Variable {
        $variable = new Variable();
        $variable->setType($type)
            ->setPropertyPath($propertyPath)
            ->setOptions($options)
            ->setValue($value)
            ->setLabel($label);

        return $variable;
    }
}
