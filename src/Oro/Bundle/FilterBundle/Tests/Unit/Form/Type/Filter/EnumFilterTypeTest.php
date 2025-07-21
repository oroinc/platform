<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Provider\EnumOptionsProvider;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class EnumFilterTypeTest extends TypeTestCase
{
    private const TEST_ENUM_CLASS = 'Extend\Entity\EV_Test_Enum';

    private TranslatorInterface&MockObject $translator;
    private EnumOptionsProvider&MockObject $provider;
    private EnumFilterType $type;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->provider = $this->createMock(EnumOptionsProvider::class);

        $this->type = new EnumFilterType($this->translator, $this->provider);
    }

    private function getOptionsResolver(): OptionsResolver
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(['null_value' => null]);

        return $resolver;
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
    ): void {
        if (null !== $enumCode) {
            $this->provider->expects(self::once())
                ->method('getEnumChoicesByCode')
                ->with($enumCode)
                ->willReturn($values);
        }

        $this->translator->expects(self::any())
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
        self::assertEquals($expectedOptions, $resolvedOptions);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionsProvider(): array
    {
        return [
            [
                'enumCode' => 'test_enum',
                'class' => null,
                'nullValue' => null,
                'values' => ['Value1' => 'val1'],
                'fieldOptions' => null,
                'expectedOptions' => [
                    'enum_code' => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class' => EnumOption::class,
                    'null_value' => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices' => [
                            'Value1' => 'val1'
                        ],
                    ],
                ]
            ],
            [
                'enumCode' => 'test_enum',
                'class' => null,
                'nullValue' => ':empty:',
                'values' => ['Value1' => 'val1'],
                'fieldOptions' => null,
                'expectedOptions' => [
                    'enum_code' => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class' => EnumOption::class,
                    'null_value' => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices' => [
                            'None' => ':empty:',
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            [
                'enumCode' => null,
                'class' => self::TEST_ENUM_CLASS,
                'nullValue' => null,
                'values' => ['Value1' => 'val1'],
                'fieldOptions' => null,
                'expectedOptions' => [
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'enum_code' => null,
                    'class' => self::TEST_ENUM_CLASS,
                    'null_value' => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices' => [
                        ],
                    ]
                ]
            ],
            [
                'enumCode' => 'test_enum',
                'class' => self::TEST_ENUM_CLASS,
                'nullValue' => ':empty:',
                'values' => ['Value1' => 'val1'],
                'fieldOptions' => null,
                'expectedOptions' => [
                    'enum_code' => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class' => self::TEST_ENUM_CLASS,
                    'null_value' => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices' => [
                            'None' => ':empty:',
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            [
                'enumCode' => 'test_enum',
                'class' => self::TEST_ENUM_CLASS,
                'nullValue' => null,
                'values' => ['Value1' => 'val1'],
                'fieldOptions' => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code' => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class' => self::TEST_ENUM_CLASS,
                    'null_value' => null,
                    'field_options' => [
                        'multiple' => false,
                        'choices' => [
                            'Value1' => 'val1'
                        ],
                    ]
                ]
            ],
            'numeric choice keys' => [
                'enumCode' => 'test_enum',
                'class' => self::TEST_ENUM_CLASS,
                'nullValue' => ':empty:',
                'values' => ['Value1' => 1],
                'fieldOptions' => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code' => 'test_enum',
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class' => self::TEST_ENUM_CLASS,
                    'null_value' => ':empty:',
                    'field_options' => [
                        'multiple' => false,
                        'choices' => [
                            'None' => ':empty:',
                            'Value1' => 1
                        ],
                    ]
                ]
            ]
        ];
    }

    public function testClassNormalizerOptionsException(): void
    {
        $this->expectException(InvalidOptionsException::class);
        $this->expectExceptionMessage('Either "class" or "enum_code" must option must be set.');

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code' => null,
            'class' => null,
            'null_value' => ':empty:'
        ]);
    }

    public function testGetParent()
    {
        self::assertEquals(ChoiceFilterType::class, $this->type->getParent());
    }
}
