<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueCollectionType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumValueCollectionTypeTest extends TypeTestCase
{
    /** @var EnumValueCollectionType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $typeHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper')
            ->disableOriginalConstructor()
            ->setMethods(['getEnumCode', 'isImmutable'])
            ->getMock();

        $this->type = new EnumValueCollectionType($this->typeHelper);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        ConfigIdInterface $configId,
        $isNewConfig,
        $enumCode,
        $isImmutableAdd,
        $isImmutableDelete,
        $options,
        $expectedOptions
    ) {
        $enumValueClassName = $enumCode ? ExtendHelper::buildEnumValueClassName($enumCode) : null;

        $this->typeHelper->expects($this->any())
            ->method('getEnumCode')
            ->with(
                $configId->getClassName(),
                $configId instanceof FieldConfigId ? $configId->getFieldName() : null
            )
            ->will($this->returnValue($enumCode));
        $this->typeHelper->expects($this->any())
            ->method('isImmutable')
            ->will(
                $this->returnValueMap(
                    [
                        ['enum', $enumValueClassName, null, 'add', $isImmutableAdd],
                        ['enum', $enumValueClassName, null, 'delete', $isImmutableDelete],
                    ]
                )
            );

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options['config_id']     = $configId;
        $options['config_is_new'] = $isNewConfig;

        $resolvedOptions = $resolver->resolve($options);

        $this->assertSame($configId, $resolvedOptions['config_id']);
        unset($resolvedOptions['config_id']);
        $this->assertEquals($isNewConfig, $resolvedOptions['config_is_new']);
        unset($resolvedOptions['config_is_new']);
        $this->assertFalse($resolvedOptions['handle_primary']);
        unset($resolvedOptions['handle_primary']);
        $this->assertEquals('oro_entity_extend_enum_value', $resolvedOptions['type']);
        unset($resolvedOptions['type']);

        $this->assertEquals($expectedOptions, $resolvedOptions);
    }

    /**
     * @return OptionsResolver
     */
    protected function getOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults(
            [
                'config_id'         => null,
                'config_is_new'     => false,
                'disabled'          => false,
                'allow_add'         => true,
                'allow_delete'      => true,
                'validation_groups' => true,
                'options'           => []
            ]
        );

        return $resolver;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setDefaultOptionsProvider()
    {
        return [
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => null,
                'isImmutableAdd'    => false,
                'isImmutableDelete' => false,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => true,
                    'allow_delete'      => true,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => false,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => true,
                    'allow_delete'      => true,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => false,
                'options'           => [
                    'disabled' => true,
                ],
                'expectedOptions'   => [
                    'disabled'          => true,
                    'allow_add'         => false,
                    'allow_delete'      => false,
                    'validation_groups' => false,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => false,
                'options'           => [
                    'allow_add'    => false,
                    'allow_delete' => false,
                ],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => false,
                    'allow_delete'      => false,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => true,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => false,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => true,
                    'allow_add'         => false,
                    'allow_delete'      => false,
                    'validation_groups' => false,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => true,
                'isImmutableDelete' => false,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => false,
                    'allow_delete'      => true,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => true,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => true,
                    'allow_delete'      => false,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => false
                    ]
                ]
            ],
            [
                'configId'          => new FieldConfigId('multiEnum', 'Test\Entity', 'testField', 'multiEnum'),
                'isNewConfig'       => false,
                'enumCode'          => 'test_enum',
                'isImmutableAdd'    => false,
                'isImmutableDelete' => true,
                'options'           => [],
                'expectedOptions'   => [
                    'disabled'          => false,
                    'allow_add'         => true,
                    'allow_delete'      => false,
                    'validation_groups' => true,
                    'options'           => [
                        'allow_multiple_selection' => true
                    ]
                ]
            ],
        ];
    }

    public function testBuildViewForEnum()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum');

        $form    = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $view    = new FormView();
        $options = ['config_id' => $configId];

        $this->type->buildView($view, $form, $options);
        $this->assertFalse($view->vars['multiple']);
    }

    public function testBuildViewForMultiEnum()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'multiEnum');

        $form    = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $view    = new FormView();
        $options = ['config_id' => $configId];

        $this->type->buildView($view, $form, $options);
        $this->assertTrue($view->vars['multiple']);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_enum_value_collection',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'oro_collection',
            $this->type->getParent()
        );
    }
}
