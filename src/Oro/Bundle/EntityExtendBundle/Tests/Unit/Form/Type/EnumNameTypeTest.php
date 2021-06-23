<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumNameType;
use Oro\Bundle\EntityExtendBundle\Form\Util\EnumTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendDbIdentifierNameGenerator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueEnumName;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class EnumNameTypeTest extends TypeTestCase
{
    /** @var EnumNameType */
    private $type;

    /** @var EnumTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $typeHelper;

    /** @var ExtendDbIdentifierNameGenerator */
    private $nameGenerator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->typeHelper = $this->getMockBuilder(EnumTypeHelper::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['hasEnumCode'])
            ->getMock();
        $this->nameGenerator = new ExtendDbIdentifierNameGenerator();

        $this->type = new EnumNameType($this->typeHelper, $this->nameGenerator);
    }

    /**
     * @dataProvider configureOptionsProvider
     */
    public function testConfigureOptions(
        ConfigIdInterface $configId,
        bool $isNewConfig,
        bool $hasEnumCode,
        array $options,
        array $expectedOptions
    ) {
        $fieldName = $configId instanceof FieldConfigId ? $configId->getFieldName() : null;

        $this->typeHelper->expects($this->any())
            ->method('hasEnumCode')
            ->with($configId->getClassName(), $fieldName)
            ->willReturn($hasEnumCode);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

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
            ->willReturn(true);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['config_id' => $configId]);

        $this->assertCount(2, $resolvedOptions['constraints']);
        $this->assertInstanceOf(
            NotBlank::class,
            $resolvedOptions['constraints'][0]
        );
        $this->assertInstanceOf(
            Length::class,
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
            ->willReturn(false);

        $resolver = $this->getOptionsResolver();
        $this->type->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['config_id' => $configId]);

        $this->assertCount(5, $resolvedOptions['constraints']);

        $this->assertInstanceOf(
            NotBlank::class,
            $resolvedOptions['constraints'][0]
        );

        $this->assertInstanceOf(
            Length::class,
            $resolvedOptions['constraints'][1]
        );
        $this->assertEquals($this->nameGenerator->getMaxEnumCodeSize(), $resolvedOptions['constraints'][1]->max);

        $this->assertInstanceOf(
            Regex::class,
            $resolvedOptions['constraints'][2]
        );
        $this->assertEquals('/^[\w- ]*$/', $resolvedOptions['constraints'][2]->pattern);
        $this->assertEquals(EnumNameType::INVALID_NAME_MESSAGE, $resolvedOptions['constraints'][2]->message);

        $this->assertInstanceOf(
            Callback::class,
            $resolvedOptions['constraints'][3]
        );
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation')
            ->with(EnumNameType::INVALID_NAME_MESSAGE);
        call_user_func($resolvedOptions['constraints'][3]->callback, '!@#$', $context);

        $this->assertInstanceOf(
            UniqueEnumName::class,
            $resolvedOptions['constraints'][4]
        );
        $this->assertEquals($configId->getClassName(), $resolvedOptions['constraints'][4]->entityClassName);
        $this->assertEquals($configId->getFieldName(), $resolvedOptions['constraints'][4]->fieldName);
    }

    public function testGetParent()
    {
        $this->assertEquals(TextType::class, $this->type->getParent());
    }
}
