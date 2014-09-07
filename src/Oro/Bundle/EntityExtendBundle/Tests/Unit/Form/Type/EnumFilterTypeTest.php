<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\ChoiceFilterType;

class EnumFilterTypeTest extends TypeTestCase
{
    /** @var EnumFilterType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $doctrine;

    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->doctrine   = $this->getMockBuilder('Doctrine\Common\Persistence\ManagerRegistry')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new EnumFilterType($this->translator, $this->doctrine);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        $enumCode,
        $class,
        $nullValue,
        $fieldOptions,
        $expectedOptions
    ) {
        $values = [
            new TestEnumValue('val1', 'Value1')
        ];

        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(array('getResult'))
            ->getMockForAbstractClass();
        $query->expects($this->any())
            ->method('getResult')
            ->will($this->returnValue($values));

        $qb = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();
        $qb->expects($this->any())
            ->method('orderBy')
            ->with('o.priority')
            ->will($this->returnSelf());
        $qb->expects($this->any())
            ->method('getQuery')
            ->will($this->returnValue($query));

        $repo = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrine->expects($this->any())
            ->method('getRepository')
            ->with($class !== null ? $class : ExtendHelper::buildEnumValueClassName($enumCode))
            ->will($this->returnValue($repo));
        $repo->expects($this->any())
            ->method('createQueryBuilder')
            ->with('o')
            ->will($this->returnValue($qb));

        $this->translator->expects($this->any())
            ->method('trans')
            ->with('oro.entity_extend.datagrid.enum.filter.empty')
            ->will($this->returnValue('None'));

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

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
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
                    'class'         => ExtendHelper::buildEnumValueClassName('test_enum'),
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
                'enumCode'        => 'test_enum',
                'class'           => null,
                'nullValue'       => ':empty:',
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => 'test_enum',
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
                'class'           => 'Test\EnumValue',
                'nullValue'       => null,
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'class'         => 'Test\EnumValue',
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
                'class'           => 'Test\EnumValue',
                'nullValue'       => ':empty:',
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'class'         => 'Test\EnumValue',
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
                'class'           => 'Test\EnumValue',
                'nullValue'       => null,
                'fieldOptions'    => [
                    'multiple' => false
                ],
                'expectedOptions' => [
                    'enum_code'     => null,
                    'class'         => 'Test\EnumValue',
                    'null_value'    => null,
                    'field_options' => [
                        'multiple' => false,
                        'choices'  => [
                            'val1' => 'Value1'
                        ]
                    ]
                ]
            ],
            [
                'enumCode'        => null,
                'class'           => '',
                'nullValue'       => ':empty:',
                'fieldOptions'    => null,
                'expectedOptions' => [
                    'enum_code'     => null,
                    'class'         => '',
                    'null_value'    => ':empty:',
                    'field_options' => [
                        'multiple' => true,
                        'choices'  => [
                            ':empty:' => 'None'
                        ]
                    ]
                ]
            ],
        ];
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
