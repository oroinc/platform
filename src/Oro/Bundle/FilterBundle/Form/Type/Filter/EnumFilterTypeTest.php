<?php

namespace Oro\Bundle\FilterBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\FilterBundle\Form\Type\Filter\EnumFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumFilterTypeTest extends TypeTestCase
{
    /** @var EnumFilterType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->provider   = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Provider\EnumValueProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new EnumFilterType($this->translator, $this->provider);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
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
            require_once(realpath(__DIR__ . DIRECTORY_SEPARATOR . 'Stub' . DIRECTORY_SEPARATOR . $fileName));

            $this->provider->expects($this->once())
                ->method('getEnumChoices')
                ->with($enumValueClassName)
                ->will($this->returnValue($values));
        }

        $this->translator->expects($this->any())
            ->method('trans')
            ->will($this->returnValue('None'));

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
    public function setDefaultOptionsProvider()
    {
        return [
            [
                'enumCode'        => 'test_enum',
                'class'           => null,
                'nullValue'       => null,
                'values'          => ['val1' => 'Value1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
                    'operator_choices' => [
                        1 => 'None',
                        2 =>  'None'
                    ],
                    'class'         => ExtendHelper::buildEnumValueClassName('test_enum'),
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'val1' => 'Value1'
                        ]
                    ],
                ]
            ],
            [
                'enumCode'        => 'test_enum',
                'class'           => null,
                'nullValue'       => ':empty:',
                'values'          => ['val1' => 'Value1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
                    'operator_choices' => [
                        1 => 'None',
                        2 =>  'None'
                    ],
                    'class'         => ExtendHelper::buildEnumValueClassName('test_enum'),
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            ':empty:' => 'None',
                            'val1'    => 'Value1'
                        ]
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => null,
                'values'          => ['val1' => 'Value1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'operator_choices' => [
                        1 => 'None',
                        2 =>  'None'
                    ],
                    'enum_code'     => null,
                    'class'         => 'Extend\Entity\EV_Test_Enum',
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            'val1' => 'Value1'
                        ]
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => ':empty:',
                'values'          => ['val1' => 'Value1'],
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        1 => 'None',
                        2 =>  'None'
                    ],
                    'class'         => 'Extend\Entity\EV_Test_Enum',
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            ':empty:' => 'None',
                            'val1'    => 'Value1'
                        ]
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => null,
                'values'          => ['val1' => 'Value1'],
                'fieldOptions'    => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        1 => 'None',
                        2 => 'None'
                    ],
                    'class'         => 'Extend\Entity\EV_Test_Enum',
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => false,
                        'choices'  => [
                            'val1' => 'Value1'
                        ]
                    ]
                ]
            ],
            'numeric choice keys' => [
                'enumCode'        => null,
                'class'           => 'Extend\Entity\EV_Test_Enum',
                'nullValue'       => ':empty:',
                'values'          => [1 => 'Value1'],
                'fieldOptions'    => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code'     => null,
                    'operator_choices' => [
                        1 => 'None',
                        2 => 'None'
                    ],
                    'class'         => 'Extend\Entity\EV_Test_Enum',
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => false,
                        'choices'  => [
                            ':empty:' => 'None',
                            1 => 'Value1'
                        ]
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
        $this->type->setDefaultOptions($resolver);
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
        $this->type->setDefaultOptions($resolver);
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

    public function testGetName()
    {
        $this->assertEquals(
            EnumFilterType::NAME,
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            ChoiceFilterType::NAME,
            $this->type->getParent()
        );
    }
}
