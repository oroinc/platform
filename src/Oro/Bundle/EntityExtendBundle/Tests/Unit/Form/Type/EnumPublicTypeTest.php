<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumPublicType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

class EnumPublicTypeTest extends TypeTestCase
{
    /** @var EnumPublicType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $typeHelper;

    protected function setUp()
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper')
            ->disableOriginalConstructor()
            ->setMethods(['isSystem', 'getEnumCode', 'isImmutable', 'hasOtherReferences'])
            ->getMock();

        $this->type = new EnumPublicType($this->typeHelper);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        ConfigIdInterface $configId,
        $isNewConfig,
        $enumCode,
        $isSystem,
        $isImmutablePublic,
        $hasOtherReferences,
        $options,
        $expectedOptions
    ) {
        $fieldName          = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;
        $enumValueClassName = $enumCode ? ExtendHelper::buildEnumValueClassName($enumCode) : null;

        $this->typeHelper->expects($this->any())
            ->method('getEnumCode')
            ->with($configId->getClassName(), $fieldName)
            ->will($this->returnValue($enumCode));
        $this->typeHelper->expects($this->any())
            ->method('isSystem')
            ->with($configId->getClassName(), $fieldName)
            ->will($this->returnValue($isSystem));
        $this->typeHelper->expects($this->any())
            ->method('isImmutable')
            ->with('enum', $enumValueClassName, null, 'public')
            ->will($this->returnValue($isImmutablePublic));
        $this->typeHelper->expects($this->any())
            ->method('hasOtherReferences')
            ->with($enumCode, $configId->getClassName(), $fieldName)
            ->will($this->returnValue($hasOtherReferences));

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options['config_id']     = $configId;
        $options['config_is_new'] = $isNewConfig;

        $resolvedOptions = $resolver->resolve($options);

        $this->assertSame($configId, $resolvedOptions['config_id']);
        unset($resolvedOptions['config_id']);
        $this->assertEquals($isNewConfig, $resolvedOptions['config_is_new']);
        unset($resolvedOptions['config_is_new']);

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
                'validation_groups' => true
            ]
        );

        return $resolver;
    }

    public function setDefaultOptionsProvider()
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

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_enum_public',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'choice',
            $this->type->getParent()
        );
    }
}
