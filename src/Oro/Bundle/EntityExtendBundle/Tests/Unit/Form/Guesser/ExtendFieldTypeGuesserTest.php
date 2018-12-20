<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Guesser;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Form\Guesser\ExtendFieldTypeGuesser;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\Decimal;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Guess\TypeGuess;
use Symfony\Component\Validator\Constraints\Length;

class ExtendFieldTypeGuesserTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var string
     */
    const CLASS_NAME = 'Oro\Bundle\SomeBundle\Entity\SomeClassName';

    /**
     * @var string
     */
    const CLASS_PROPERTY = 'SomeClassProperty';

    /**
     * @var string
     */
    const PROPERTY_TYPE = 'bigint';

    /**
     * @var array
     */
    private static $entityConfig = [
        'label' => self::SOME_LABEL
    ];

    /**
     * @var string
     */
    const SOME_LABEL = 'someLabel';

    /**
     * @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $managerRegistry;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityConfigProvider;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $formConfigProvider;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $extendConfigProvider;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $enumConfigProvider;

    /**
     * @var ExtendFieldTypeGuesser
     */
    private $guesser;

    protected function setUp()
    {
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->entityConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extendConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->enumConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->guesser = new ExtendFieldTypeGuesser(
            $this->managerRegistry,
            $this->entityConfigProvider,
            $this->formConfigProvider,
            $this->extendConfigProvider,
            $this->enumConfigProvider
        );
    }

    /**
     * @param bool $hasConfig
     */
    private function expectsHasExtendConfig($hasConfig)
    {
        $this->extendConfigProvider
            ->expects($this->once())
            ->method('hasConfig')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($hasConfig);
    }

    /**
     * @param $scopeName
     * @param $scopeOptions
     * @param $fieldType
     * @return Config
     */
    private function createFieldConfig($scopeName, $scopeOptions, $fieldType)
    {
        return new Config(
            new FieldConfigId($scopeName, self::CLASS_NAME, self::CLASS_PROPERTY, $fieldType),
            $scopeOptions
        );
    }

    /**
     * @param ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider
     * @param string $fieldType
     * @param string $scopeName
     * @param array $scopeOptions
     */
    private function createConfigProviderExpectation($configProvider, $fieldType, $scopeName, array $scopeOptions)
    {
        $config = $this->createFieldConfig($scopeName, $scopeOptions, $fieldType);

        $configProvider
            ->expects($this->once())
            ->method('getConfig')
            ->with(self::CLASS_NAME, self::CLASS_PROPERTY)
            ->willReturn($config);
    }

    /**
     * @param array $scopeOptions
     * @param string $fieldType
     */
    private function expectsGetFormConfig(array $scopeOptions, $fieldType = self::PROPERTY_TYPE)
    {
        $this->createConfigProviderExpectation($this->formConfigProvider, $fieldType, 'form', $scopeOptions);
    }

    /**
     * @param array $scopeOptions
     * @param string $fieldType
     */
    private function expectsGetExtendConfig(array $scopeOptions, $fieldType = self::PROPERTY_TYPE)
    {
        $this->createConfigProviderExpectation($this->extendConfigProvider, $fieldType, 'extend', $scopeOptions);
    }

    /**
     * @param array $scopeOptions
     * @param string $fieldType
     */
    private function expectsGetEntityConfig(array $scopeOptions, $fieldType = self::PROPERTY_TYPE)
    {
        $this->createConfigProviderExpectation($this->entityConfigProvider, $fieldType, 'entity', $scopeOptions);
    }

    /**
     * @param TypeGuess $typeGuess
     */
    private function assertIsDefaultTypeGuess($typeGuess)
    {
        $defaultTypeGuess = new TypeGuess(TextType::class, [], TypeGuess::LOW_CONFIDENCE);
        $this->assertEquals($defaultTypeGuess, $typeGuess);
    }

    /**
     * @param TypeGuess $typeGuess
     * @param array $options
     * @param string $type
     */
    private function assertTypeGuess($typeGuess, array $options = [], $type = 'text')
    {
        $defaultTypeGuess = new TypeGuess($type, $options, TypeGuess::HIGH_CONFIDENCE);
        $this->assertEquals($defaultTypeGuess, $typeGuess);
    }

    public function testGuessTypeWhenNoExtendConfigExists()
    {
        $this->expectsHasExtendConfig(false);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenExtendConfigExistsAndFormConfigNotEnabled()
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => false]);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenFormScopeHasTypeButNotApplicable()
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig([
            'is_enabled' => true,
            'type' => 'text'
        ]);

        $this->expectsGetExtendConfig([]);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenFormScopeHasTypeAndFieldIsApplicable()
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig([
            'is_enabled' => true,
            'type' => 'text'
        ]);

        $this->expectsGetExtendConfig([
            'owner' => ExtendScope::ORIGIN_CUSTOM,
        ]);

        $this->expectsGetEntityConfig(self::$entityConfig);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, [
            'label' => self::SOME_LABEL,
            'required' => false,
            'block' => 'general'
        ]);
    }

    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapNotExists()
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true]);

        $this->expectsGetExtendConfig([]);

        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsButNotApplicable()
    {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true]);

        $this->expectsGetExtendConfig([]);

        $this->guesser->addExtendTypeMapping('bigint', 'text');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertIsDefaultTypeGuess($typeGuess);
    }

    /**
     * @return array
     */
    public function simpleTypeDataProvider()
    {
        return [
            'boolean' => [
                'fieldType' => 'boolean',
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'configs' => ['allowClear' => false],
                    'choices' => [
                        'No' => false,
                        'Yes' => true
                    ]
                ]
            ],
            'string' => [
                'fieldType' => 'string',
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                    'length' => 17
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'constraints' => [
                        new Length(['max' => 17])
                    ]
                ]
            ],
            'decimal' => [
                'fieldType' => 'decimal',
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                    'precision' => 8,
                    'scale' => 2
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'constraints' => [
                        new Decimal(['precision' => 8, 'scale' => 2])
                    ]
                ]
            ]
        ];
    }

    /**
     * @dataProvider simpleTypeDataProvider
     *
     * @param string $fieldType
     * @param array $extendConfig
     * @param array $expectedOptions
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsAndFieldIsApplicableAndFieldTypeIsSimple(
        $fieldType,
        array $extendConfig,
        array $expectedOptions
    ) {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true], $fieldType);

        $this->expectsGetExtendConfig($extendConfig);

        $this->expectsGetEntityConfig(self::$entityConfig);

        $this->guesser->addExtendTypeMapping($fieldType, 'customType');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, $expectedOptions, 'customType');
    }

    /**
     * @return array
     */
    public function relationTypesDataProvider()
    {
        $relationOptions = [
            'fieldType' => RelationType::MANY_TO_ONE,
            'extendConfig' => [
                'owner' => ExtendScope::ORIGIN_CUSTOM,
                'target_entity' => 'Oro\Bundle\SomeBundle\Entity\SomeTargetEntity',
                'target_field' => 'SomeTargetField'
            ],
            'expectedOptions' => [
                'label' => self::SOME_LABEL,
                'required' => false,
                'block' => 'SomeTargetEntity',
                'initial_elements' => null,
                'default_element' => 'default_SomeClassProperty',
                'class' => 'Oro\Bundle\SomeBundle\Entity\SomeTargetEntity',
                'selector_window_title' => 'Select SomeTargetEntity',
                'block_config' => [
                    'SomeTargetEntity' => [
                        'title' => null,
                        'subblocks' => [
                            [
                                'useSpan' => false
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return [
            'many_to_one' => [
                'fieldType' => RelationType::MANY_TO_ONE,
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                    'target_entity' => 'Oro\Bundle\SomeBundle\Entity\SomeTargetEntity',
                    'target_field' => 'SomeTargetField'
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'entity_class' => 'Oro\Bundle\SomeBundle\Entity\SomeTargetEntity',
                    'configs' => [
                        'placeholder'   => 'oro.form.choose_value',
                        'component'  => 'relation',
                        'target_entity' => 'Oro_Bundle_SomeBundle_Entity_SomeTargetEntity',
                        'target_field'  => 'SomeTargetField',
                        'properties'    => ['SomeTargetField'],
                    ]
                ]
            ],
            'one_to_many' => array_merge($relationOptions, ['fieldType' => RelationType::ONE_TO_MANY]),
            'many_to_many' => array_merge($relationOptions, ['fieldType' => RelationType::MANY_TO_MANY])
        ];
    }

    /**
     * @dataProvider relationTypesDataProvider
     *
     * @param string $fieldType
     * @param array $extendConfig
     * @param array $expectedOptions
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsAndFieldIsApplicable(
        $fieldType,
        array $extendConfig,
        array $expectedOptions
    ) {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true], $fieldType);

        $fieldConfig = $this->createFieldConfig('extend', $extendConfig, $fieldType);
        $targetConfig = new Config(new EntityConfigId('extend', $extendConfig['target_entity']), []);

        $this->extendConfigProvider
            ->expects($this->exactly(2))
            ->method('getConfig')
            ->willReturnOnConsecutiveCalls($fieldConfig, $targetConfig);

        $this->expectsGetEntityConfig(self::$entityConfig);

        $this->guesser->addExtendTypeMapping($fieldType, 'text');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, $expectedOptions);
    }

    /**
     * @return array
     */
    public function enumTypesDataProvider()
    {
        return [
            'enum' => [
                'fieldType' => 'enum',
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                ],
                'enumConfig' => [
                    'enum_code' => 'SomeEnumCode'
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'enum_code' => 'SomeEnumCode'
                ]
            ],
            'multiEnum' => [
                'fieldType' => 'multiEnum',
                'extendConfig' => [
                    'owner' => ExtendScope::ORIGIN_CUSTOM,
                ],
                'enumConfig' => [
                    'enum_code' => 'SomeEnumCode'
                ],
                'expectedOptions' => [
                    'label' => self::SOME_LABEL,
                    'required' => false,
                    'block' => 'general',
                    'enum_code' => 'SomeEnumCode',
                    'expanded' => true
                ]
            ]
        ];
    }

    /**
     * @dataProvider enumTypesDataProvider
     *
     * @param string $fieldType
     * @param array $extendConfig
     * @param array $enumConfig
     * @param array $expectedOptions
     */
    public function testGuessTypeWhenFormScopeHasNoTypeAndTypeMapExistsAndFieldIsApplicableWithEnumTypes(
        $fieldType,
        array $extendConfig,
        array $enumConfig,
        array $expectedOptions
    ) {
        $this->expectsHasExtendConfig(true);
        $this->expectsGetFormConfig(['is_enabled' => true], $fieldType);

        $this->expectsGetExtendConfig($extendConfig);
        $this->expectsGetEntityConfig(self::$entityConfig);

        $this->enumConfigProvider
            ->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->createFieldConfig('enum', $enumConfig, $fieldType));

        $this->guesser->addExtendTypeMapping($fieldType, 'text');
        $typeGuess = $this->guesser->guessType(self::CLASS_NAME, self::CLASS_PROPERTY);

        $this->assertTypeGuess($typeGuess, $expectedOptions);
    }
}
