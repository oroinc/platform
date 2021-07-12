<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\AttributeField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\AttributeFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeFieldValidatorTest extends ConstraintValidatorTestCase
{
    /** @var FieldNameValidationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $validationHelper;

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigProvider;

    protected function setUp(): void
    {
        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new AttributeFieldValidator(
            $this->validationHelper,
            $this->attributeConfigProvider
        );
    }

    public function testValidateWhenWrongValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Only Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel is supported, stdClass is given'
        );
        $constraint = new AttributeField();

        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testValidateWhenNoConfig()
    {
        $fieldName = 'testField';
        $className = 'testClass';
        $entityConfigModel = new EntityConfigModel($className);
        $fieldConfigModel = new FieldConfigModel($fieldName, 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        $this->validationHelper->expects($this->any())
            ->method('normalizeFieldName')
            ->willReturnArgument(0);
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn([]);

        $this->validator->validate($fieldConfigModel, new AttributeField());

        $this->assertNoViolation();
    }

    public function testValidateWhenIsAttribute()
    {
        $fieldName = 'testField';
        $className = 'testClass';
        $entityConfigModel = new EntityConfigModel($className);
        $fieldConfigModel = new FieldConfigModel($fieldName, 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        $this->validationHelper->expects($this->any())
            ->method('normalizeFieldName')
            ->willReturnCallback(function ($value) {
                return $value;
            });
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn([
                new Config(new FieldConfigId('attribute', $className, $fieldName), ['is_attribute' => true]),
            ]);

        $this->validator->validate($fieldConfigModel, new AttributeField());

        $this->assertNoViolation();
    }

    public function testValidate()
    {
        $fieldName = 'testField';
        $className = 'testClass';
        $entityConfigModel = new EntityConfigModel($className);
        $fieldConfigModel = new FieldConfigModel($fieldName, 'string');
        $fieldConfigModel->setEntity($entityConfigModel);

        $this->validationHelper->expects($this->any())
            ->method('normalizeFieldName')
            ->willReturnCallback(function ($value) {
                return $value;
            });
        $this->attributeConfigProvider->expects($this->once())
            ->method('getConfigs')
            ->with($className, true)
            ->willReturn([
                new Config(new FieldConfigId('attribute', $className, $fieldName), ['is_attribute' => false]),
            ]);

        $constraint = new AttributeField();
        $this->validator->validate($fieldConfigModel, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameter('{{ field }}', $fieldName)
            ->atPath('property.path.fieldName')
            ->assertRaised();
    }
}
