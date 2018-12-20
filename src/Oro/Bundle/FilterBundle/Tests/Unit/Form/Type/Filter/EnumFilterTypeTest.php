<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type\Filter;

use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumFilterTypeTest extends TypeTestCase
{
    /** @var EnumFilterType */
    protected $type;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->createMock('Symfony\Component\Translation\TranslatorInterface');
        $this->provider   = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new EnumFilterType($this->translator, $this->provider);
    }

    /**
     * @dataProvider configureOptionsProvider
     * @param string $enumCode
     * @param string $class
     * @param string $nullValue
     * @param array $values
     * @param array|null $fieldOptions
     * @param array|null $expectedOptions
     */
    public function testConfigureOptions(
        $enumCode,
        $class,
        $nullValue,
        $values,
        $fieldOptions,
        $expectedOptions
    ) {
        $enumValueClassName = $class !== null ? $class : ExtendHelper::buildEnumValueClassName($enumCode);
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
                ->will($this->returnValue($values));
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

        $this->assertEquals($expectedOptions, $resolvedOptions);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionsProvider()
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
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => null,
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'enum_code'     => null,
                    'class'         => 'Extend\Entity\EV_Test_Enum',
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
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => ':empty:',
                'values'          => ['Value1' => 'val1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        'is any of' => 1,
                        'is not any of' => 2,
                    ],
                    'class'         => 'Extend\Entity\EV_Test_Enum',
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
                'class'           => 'Extend\Entity\EV_Test_Enum',
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
                    'class'         => 'Extend\Entity\EV_Test_Enum',
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
                'class'           => 'Extend\Entity\EV_Test_Enum',
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
                    'class'         => 'Extend\Entity\EV_Test_Enum',
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

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage Either "class" or "enum_code" must option must be set.
     */
    public function testClassNormalizerOptionsException()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code'     => null,
            'class'         => null,
            'null_value'    => ':empty:'
        ]);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage must be a child of "Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue"
     */
    public function testClassNormalizerUnexpectedEnumException()
    {
        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);
        $resolver->resolve([
            'enum_code'     => 'unknown',
            'null_value'    => ':empty:'
        ]);
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'null_value' => null
            ]
        );

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
