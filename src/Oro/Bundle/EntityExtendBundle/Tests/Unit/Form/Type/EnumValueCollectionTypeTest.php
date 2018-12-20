<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueCollectionType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumValueCollectionTypeTest extends TypeTestCase
{
    /** @var EnumValueCollectionType */
    protected $type;

    /** @var EnumTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $typeHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder(EnumTypeHelper::class)
            ->disableOriginalConstructor()
            ->setMethods(['getEnumCode', 'isImmutable'])
            ->getMock();

        $this->type = new EnumValueCollectionType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     * @param ConfigIdInterface $configId
     * @param boolean $isNewConfig
     * @param string $enumCode
     * @param boolean $isImmutableAdd
     * @param boolean $isImmutableDelete
     * @param array $options
     * @param array $expectedOptions
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        $isNewConfig,
        $enumCode,
        $isImmutableAdd,
        $isImmutableDelete,
        array $options,
        array $expectedOptions
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
        $this->type->configureOptions($resolver);

        $options['config_id']     = $configId;
        $options['config_is_new'] = $isNewConfig;

        $resolvedOptions = $resolver->resolve($options);

        $this->assertSame($configId, $resolvedOptions['config_id']);
        unset($resolvedOptions['config_id']);
        $this->assertEquals($isNewConfig, $resolvedOptions['config_is_new']);
        unset($resolvedOptions['config_is_new']);
        $this->assertFalse($resolvedOptions['handle_primary']);
        unset($resolvedOptions['handle_primary']);
        $this->assertEquals(EnumValueType::class, $resolvedOptions['entry_type']);
        unset($resolvedOptions['entry_type']);

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
                'entry_options'     => []
            ]
        );

        return $resolver;
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function configureOptionsProvider()
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
                    'entry_options'     => [
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
                    'entry_options'     => [
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
                    'entry_options'           => [
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
                    'entry_options'           => [
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
                    'entry_options'           => [
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
                    'entry_options'           => [
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
                    'entry_options'           => [
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
                    'entry_options'           => [
                        'allow_multiple_selection' => true
                    ]
                ]
            ],
        ];
    }

    public function testBuildViewForEnum()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum');

        /** @var FormInterface $form */
        $form    = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $view    = new FormView();
        $options = ['config_id' => $configId];

        $this->type->buildView($view, $form, $options);
        $this->assertFalse($view->vars['multiple']);
        $this->assertFalse($view->vars['show_form_when_empty']);
    }

    public function testBuildViewForMultiEnum()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'multiEnum');

        /** @var FormInterface $form */
        $form    = $this->getMockBuilder('Symfony\Component\Form\Test\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $view    = new FormView();
        $options = ['config_id' => $configId];

        $this->type->buildView($view, $form, $options);
        $this->assertTrue($view->vars['multiple']);
        $this->assertFalse($view->vars['show_form_when_empty']);
    }

    public function testGetParent()
    {
        $this->assertEquals(CollectionType::class, $this->type->getParent());
    }
}
