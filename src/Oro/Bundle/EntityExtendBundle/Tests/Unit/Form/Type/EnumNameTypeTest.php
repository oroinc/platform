<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumNameType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;

class EnumNameTypeTest extends TypeTestCase
{
    /** @var EnumNameType */
    protected $type;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $typeHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    protected $nameGenerator;

    protected function setUp()
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper')
            ->disableOriginalConstructor()
            ->setMethods(['hasEnumCode'])
            ->getMock();

        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();

        $this->type = new EnumNameType($this->typeHelper, $this->nameGenerator);
    }

    /**
     * @dataProvider setDefaultOptionsProvider
     */
    public function testSetDefaultOptions(
        ConfigIdInterface $configId,
        $isNewConfig,
        $hasEnumCode,
        $options,
        $expectedOptions
    ) {
        $fieldName = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;

        $this->typeHelper->expects($this->any())
            ->method('hasEnumCode')
            ->with($configId->getClassName(), $fieldName)
            ->will($this->returnValue($hasEnumCode));

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $options['config_id']     = $configId;
        $options['config_is_new'] = $isNewConfig;

        $resolvedOptions = $resolver->resolve($options);

        $this->assertSame($configId, $resolvedOptions['config_id']);
        unset($resolvedOptions['config_id']);
        $this->assertEquals($isNewConfig, $resolvedOptions['config_is_new']);
        unset($resolvedOptions['config_is_new']);
        if ($hasEnumCode) {
            $this->assertCount(2, $resolvedOptions['constraints']);
        } else {
            $this->assertCount(5, $resolvedOptions['constraints']);
        }
        unset($resolvedOptions['constraints']);

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
                'hasEnumCode'        => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => false,
                    'validation_groups' => true
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'hasEnumCode'        => true,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => false,
                    'validation_groups' => true
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => false,
                'hasEnumCode'        => true,
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
                'hasEnumCode'        => false,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => false,
                    'validation_groups' => true
                ]
            ],
            [
                'configId'           => new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum'),
                'isNewConfig'        => true,
                'hasEnumCode'        => true,
                'options'            => [],
                'expectedOptions'    => [
                    'disabled'          => true,
                    'validation_groups' => false
                ]
            ],
        ];
    }

    public function testExistingEnumNameValidators()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum');

        $this->typeHelper->expects($this->any())
            ->method('hasEnumCode')
            ->with($configId->getClassName(), $configId->getFieldName())
            ->will($this->returnValue(true));

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $resolvedOptions = $resolver->resolve(['config_id' => $configId]);

        $this->assertCount(2, $resolvedOptions['constraints']);
        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\NotBlank',
            $resolvedOptions['constraints'][0]
        );
        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Length',
            $resolvedOptions['constraints'][1]
        );
        $this->assertEquals(255, $resolvedOptions['constraints'][1]->max);
    }

    public function testNewEnumNameValidators()
    {
        $configId = new FieldConfigId('enum', 'Test\Entity', 'testField', 'enum');

        $this->typeHelper->expects($this->any())
            ->method('hasEnumCode')
            ->with($configId->getClassName(), $configId->getFieldName())
            ->will($this->returnValue(false));

        $resolver = $this->getOptionsResolver();
        $this->type->setDefaultOptions($resolver);

        $resolvedOptions = $resolver->resolve(['config_id' => $configId]);

        $this->assertCount(5, $resolvedOptions['constraints']);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\NotBlank',
            $resolvedOptions['constraints'][0]
        );

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Length',
            $resolvedOptions['constraints'][1]
        );
        $this->assertEquals($this->nameGenerator->getMaxEnumCodeSize(), $resolvedOptions['constraints'][1]->max);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Regex',
            $resolvedOptions['constraints'][2]
        );
        $this->assertEquals('/^[\w- ]*$/', $resolvedOptions['constraints'][2]->pattern);
        $this->assertEquals(EnumNameType::INVALID_NAME_MESSAGE, $resolvedOptions['constraints'][2]->message);

        $this->assertInstanceOf(
            'Symfony\Component\Validator\Constraints\Callback',
            $resolvedOptions['constraints'][3]
        );
        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $context->expects($this->once())->method('addViolation')->with(EnumNameType::INVALID_NAME_MESSAGE);
        call_user_func($resolvedOptions['constraints'][3]->methods[0], '!@#$', $context);

        $this->assertInstanceOf(
            'Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName',
            $resolvedOptions['constraints'][4]
        );
        $this->assertEquals($configId->getClassName(), $resolvedOptions['constraints'][4]->entityClassName);
        $this->assertEquals($configId->getFieldName(), $resolvedOptions['constraints'][4]->fieldName);
    }

    public function testGetName()
    {
        $this->assertEquals(
            'oro_entity_extend_enum_name',
            $this->type->getName()
        );
    }

    public function testGetParent()
    {
        $this->assertEquals(
            'text',
            $this->type->getParent()
        );
    }
}
