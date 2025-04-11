<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueCollectionType;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumValueCollectionTypeTest extends TypeTestCase
{
    /** @var EnumValueCollectionType */
    private $type;

    /** @var EnumTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $typeHelper;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder(EnumTypeHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getEnumCode', 'isImmutable'])
            ->getMock();

        $this->type = new EnumValueCollectionType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        bool $isNewConfig,
        ?string $enumCode,
        bool $isImmutableAdd,
        bool $isImmutableDelete,
        array $options,
        array $expectedOptions
    ) {
        $this->typeHelper->expects($this->any())
            ->method('getEnumCode')
            ->with(
                $configId->getClassName(),
                $configId instanceof FieldConfigId ? $configId->getFieldName() : null
            )
            ->willReturn($enumCode);
        $this->typeHelper->expects($this->any())
            ->method('isImmutable')
            ->willReturnMap([
                ['enum', $configId->getClassName(), 'testField', 'add', $isImmutableAdd],
                ['enum', $configId->getClassName(), 'testField', 'delete', $isImmutableDelete],
            ]);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options['config_id'] = $configId;
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

    private function getOptionsResolver(): OptionsResolver
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
    public function configureOptionsProvider(): array
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

        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
        $options = ['config_id' => $configId];

        $this->type->buildView($view, $form, $options);
        $this->assertFalse($view->vars['multiple']);
        $this->assertFalse($view->vars['show_form_when_empty']);
    }

    public function testBuildViewForMultiEnum()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'multiEnum');

        $form = $this->createMock(FormInterface::class);
        $view = new FormView();
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
