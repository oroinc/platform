<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Extend\Entity\EV_Test_Enum;
use Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumFilterTypeTest extends TypeTestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var EnumValueProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var EnumFilterType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = $this->createMock(EnumValueProvider::class);

        $this->type = new EnumFilterType($this->translator, $this->provider);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ?string $enumCode,
        ?string $class,
        ?string $nullValue,
        array $values,
        ?array $fieldOptions,
        ?array $expectedOptions
    ) {
        $enumValueClassName = $class ?? ExtendHelper::buildEnumValueClassName($enumCode);
        if ($enumValueClassName) {
            // AbstractEnumType require class to be instance of AbstractEnumValue
            // This may be achieved with Stub. Stub namespace does not reflect Stub path.
            // So we have to load it manually
            $fileName = ExtendHelper::getShortClassName($enumValueClassName) . '.php';
            $path = str_replace(
                '/',
                DIRECTORY_SEPARATOR,
                '/../../../../../../EntityExtendBundle/Tests/Unit/Form/Type/Stub/'
            );
            require_once(realpath(__DIR__ . $path . DIRECTORY_SEPARATOR . $fileName));

            $this->provider->expects($this->once())
                ->method('getEnumChoices')
                ->with($enumValueClassName)
                ->willReturn($values);
        }

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
                ['oro.filter.form.label_type_in', [], null, null, 'is any of'],
                ['oro.filter.form.label_type_not_in', [], null, null, 'is not any of'],
                ['oro.entity_extend.datagrid.enum.filter.empty', [], null, null, 'None'],
            ]);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options = [];
        if ($enumCode !== null) {
            $options['enum_code'] = $enumCode;
        }
        if ($class !== null) {
            $options['class'] = $class;
        }
        if ($nullValue !== null) {
            $options['null_value'] = $nullValue;
        }
        if ($fieldOptions !== null) {
            $options['field_options'] = $fieldOptions;
        }

        $resolvedOptions = $resolver->resolve($options);

        if (!isset($expectedOptions['field_options']['translatable_options'])) {
            $expectedOptions['field_options']['translatable_options'] = false;
        }
        $this->assertEquals($expectedOptions, $resolvedOptions);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionsProvider(): array
    {
        return [
            [
                'enumCode'        => 'test_enum',
                'class'           => null,
                'nullValue'       => null,
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => ExtendHelper::buildEnumValueClassName('test_enum'),
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'Value1' => 'val1'
                        ],
                    ],
                ]
            ],
            [
                'enumCode'        => 'test_enum',
                'class'           => null,
                'nullValue'       => ':empty:',
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => ExtendHelper::buildEnumValueClassName('test_enum'),
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'None' => ':empty:',
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => EV_Test_Enum::class,
                'nullValue'       => null,
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'enum_code'     => null,
                    'class'         => EV_Test_Enum::class,
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => EV_Test_Enum::class,
                'nullValue'       => ':empty:',
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => EV_Test_Enum::class,
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'None' => ':empty:',
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => EV_Test_Enum::class,
                'nullValue'       => null,
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => EV_Test_Enum::class,
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => false,
                        'choices'  => [
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            'numeric choice keys' => [
                'enumCode'        => null,
                'class'           => EV_Test_Enum::class,
                'nullValue'       => ':empty:',
                'values'          => ['Value1' => 1],
                'fieldOptions'    => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => EV_Test_Enum::class,
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => false,
                        'choices'  => [
                            'None' => ':empty:',
                            'Value1' => 1
                        ],
                    ]
                ]
            ]
        ];
    }

    public function testClassNormalizerOptionsException()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Either "class" or "enum_code" must option must be set.');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code'     => null,
            'class'         => null,
            'null_value'    => ':empty:'
        ]);
    }

    public function testClassNormalizerUnexpectedEnumException()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('must be a child of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue"');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code'     => 'unknown',
            'null_value'    => ':empty:'
        ]);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['null_value' => null]);

        return $resolver;
    }

    public function testGetParent()
    {
        $this->assertEquals(
            ChoiceFilterType::class,
            $this->type->getParent()
        );
    }
}
