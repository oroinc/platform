<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumPublicType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EnumPublicTypeTest extends TypeTestCase
{
    /** @var EnumTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $typeHelper;

    /** @var EnumPublicType */
    private $type;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder(EnumTypeHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['isSystem', 'getEnumCode', 'isImmutable', 'hasOtherReferences'])
            ->getMock();

        $this->type = new EnumPublicType($this->typeHelper);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        bool $isNewConfig,
        ?string $enumCode,
        bool $isSystem,
        bool $isImmutablePublic,
        bool $hasOtherReferences,
        array $options,
        array $expectedOptions
    ) {
        $fieldName          = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;
        $enumValueClassName = $enumCode ? ExtendHelper::buildEnumValueClassName($enumCode) : null;

        $this->typeHelper->expects($this->any())
            ->method('getEnumCode')
            ->with($configId->getClassName(), $fieldName)
            ->willReturn($enumCode);
        $this->typeHelper->expects($this->any())
            ->method('isSystem')
            ->with($configId->getClassName(), $fieldName)
            ->willReturn($isSystem);
        $this->typeHelper->expects($this->any())
            ->method('isImmutable')
            ->with('enum', $enumValueClassName, null, 'public')
            ->willReturn($isImmutablePublic);
        $this->typeHelper->expects($this->any())
            ->method('hasOtherReferences')
            ->with($enumCode, $configId->getClassName(), $fieldName)
            ->willReturn($hasOtherReferences);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $options['config_id']     = $configId;
        $options['config_is_new'] = $isNewConfig;

        $resolvedOptions = $resolver->resolve($options);

        $this->assertSame($configId, $resolvedOptions['config_id']);
        unset($resolvedOptions['config_id']);
        $this->assertEquals($isNewConfig, $resolvedOptions['config_is_new']);
        unset($resolvedOptions['config_is_new']);

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
                'validation_groups' => true
            ]
        );

        return $resolver;
    }

    public function configureOptionsProvider(): array
    {
        return [
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => null,
                'isSystem'           => false,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => false,
                    'validation_groups' => true
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => 'test_enum',
                'isSystem'           => false,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => false,
                    'validation_groups' => true
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => 'test_enum',
                'isSystem'           => false,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => false,
                'options'            => [
                    'disabled' => true,
                ],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => true,
                'enumCode'           => 'test_enum',
                'isSystem'           => false,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => 'test_enum',
                'isSystem'           => true,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => 'test_enum',
                'isSystem'           => false,
                'isImmutablePublic'  => true,
                'hasOtherReferences' => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'enumCode'           => 'test_enum',
                'isSystem'           => false,
                'isImmutablePublic'  => false,
                'hasOtherReferences' => true,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
        ];
    }

    public function testGetParent()
    {
        $this->assertEquals(ChoiceType::class, $this->type->getParent());
    }
}
