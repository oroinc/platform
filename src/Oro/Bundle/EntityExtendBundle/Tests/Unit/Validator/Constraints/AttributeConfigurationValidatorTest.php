<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\AttributeConfiguration;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\AttributeConfigurationValidator;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeConfigurationValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeTypeRegistry;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeConfigProvider;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    protected function setUp(): void
    {
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->constraint = new AttributeConfiguration();
        $this->context = $this->createContext();
        $this->validator = $this->createValidator();
        $this->validator->initialize($this->context);
    }

    /**
     * @return AttributeConfigurationValidator
     */
    protected function createValidator()
    {
        return new AttributeConfigurationValidator(
            $this->attributeTypeRegistry,
            $this->attributeConfigProvider,
            $this->configManager
        );
    }

    public function testValidateWithWrongValue()
    {
        $this->attributeConfigProvider->expects($this->never())
            ->method('getConfig');
        $this->attributeTypeRegistry->expects($this->never())
            ->method('getAttributeType');
        $this->configManager->expects($this->never())
            ->method('createFieldConfigByModel');
        $this->validator->validate(new \stdClass(), $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateEntityIsAttributeFalse()
    {
        $className = 'ClassName';
        $entity = new EntityConfigModel($className);
        $value = new FieldConfigModel();
        $value->setEntity($entity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(false);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel');
        $this->attributeTypeRegistry->expects($this->never())
            ->method('getAttributeType');
        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenFieldConfigNotAttribute()
    {
        $className = 'ClassName';
        $entity = new EntityConfigModel($className);
        $value = new FieldConfigModel();
        $value->setEntity($entity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($value, 'attribute')
            ->willReturn($this->createMock(ConfigInterface::class));
        $this->attributeTypeRegistry->expects($this->never())
            ->method('getAttributeType');
        $this->validator->validate($value, $this->constraint);

        $this->assertNoViolation();
    }

    public function testValidateWithoutViolations()
    {
        $className = 'ClassName';
        $entity = new EntityConfigModel($className);
        $value = new FieldConfigModel(null, 'fieldType');
        $value->setEntity($entity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($value, 'attribute')
            ->willReturn($fieldConfig);

        $attributeType = $this->createMock(AttributeTypeInterface::class);
        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($value)
            ->willReturn($attributeType);

        $fieldConfig->expects($this->any())
            ->method('is')
            ->willReturnMap([
                ['is_attribute', true, true],
            ]);

        $this->validator->validate($value, $this->constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider validateDataProvider
     */
    public function testValidate(array $options, array $expectedViolationParams)
    {
        $fieldType = 'fieldType';
        $className = 'ClassName';

        $entity = new EntityConfigModel($className);
        $value = new FieldConfigModel(null, $fieldType);
        $value->setEntity($entity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($value, 'attribute')
            ->willReturn($fieldConfig);

        $attributeType = $this->createMock(AttributeTypeInterface::class);
        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($value)
            ->willReturn($attributeType);

        $fieldConfig->expects($this->any())
            ->method('is')
            ->willReturnMap($options);

        $this->validator->validate($value, $this->constraint);
        $expectedViolationParams['{{ type }}'] = $fieldType;
        $this->buildViolation($this->constraint->message)
            ->atPath('')
            ->setInvalidValue(null)
            ->setParameters($expectedViolationParams)
            ->assertRaised();
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        return [
            'when filterable violation' => [
                'options' => [
                    ['is_attribute', true, true],
                    ['filterable', true, true],
                ],
                'expectedViolationParams' => [
                    '{{ option }}' => 'filterable',
                ],
            ],
            'when sortable violation' => [
                'options' => [
                    ['is_attribute', true, true],
                    ['filterable', true, false],
                    ['sortable', true, true],
                ],
                'expectedViolationParams' => [
                    '{{ option }}' => 'sortable',
                ],
            ],
            'when searchable violation' => [
                'options' => [
                    ['is_attribute', true, true],
                    ['filterable', true, false],
                    ['sortable', true, false],
                    ['searchable', true, true],
                ],
                'expectedViolationParams' => [
                    '{{ option }}' => 'searchable',
                ],
            ],
        ];
    }

    public function testValidateMultiple()
    {
        $fieldType = 'fieldType';
        $className = 'ClassName';

        $entity = new EntityConfigModel($className);
        $value = new FieldConfigModel(null, $fieldType);
        $value->setEntity($entity);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->once())
            ->method('is')
            ->with('has_attributes')
            ->willReturn(true);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->with($className)
            ->willReturn($config);

        $fieldConfig = $this->createMock(ConfigInterface::class);
        $this->configManager->expects($this->once())
            ->method('createFieldConfigByModel')
            ->with($value, 'attribute')
            ->willReturn($fieldConfig);

        $attributeType = $this->createMock(AttributeTypeInterface::class);
        $this->attributeTypeRegistry->expects($this->once())
            ->method('getAttributeType')
            ->with($value)
            ->willReturn($attributeType);

        $fieldConfig->expects($this->any())
            ->method('is')
            ->willReturn([
                ['is_attribute', true, true],
                ['filterable', true, true],
                ['sortable', true, true],
                ['searchable', true, true],
            ]);

        $this->validator->validate($value, $this->constraint);
        $expectedViolationParam['{{ type }}'] = $fieldType;
        $this->buildViolation($this->constraint->message)
            ->atPath('')
            ->setInvalidValue(null)
            ->setParameters([
                '{{ type }}' => $fieldType,
                '{{ option }}' => 'filterable'
            ])
            ->buildNextViolation($this->constraint->message)
            ->atPath('')
            ->setInvalidValue(null)
            ->setParameters([
                '{{ type }}' => $fieldType,
                '{{ option }}' => 'sortable'
            ])
            ->buildNextViolation($this->constraint->message)
            ->atPath('')
            ->setInvalidValue(null)
            ->setParameters([
                '{{ type }}' => $fieldType,
                '{{ option }}' => 'searchable'
            ])
            ->assertRaised();
    }
}
