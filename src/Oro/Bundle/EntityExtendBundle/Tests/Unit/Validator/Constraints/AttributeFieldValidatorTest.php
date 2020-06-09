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
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class AttributeFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var FieldNameValidationHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $validationHelper;

    /**
     * @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $attributeConfigProvider;

    /**
     * @var AttributeFieldValidator
     */
    private $validator;

    protected function setUp(): void
    {
        $this->validationHelper = $this->createMock(FieldNameValidationHelper::class);
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $this->validator = new AttributeFieldValidator(
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
        $context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($context);
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

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $this->validator->initialize($context);
        $this->validator->validate($fieldConfigModel, new AttributeField());
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

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method('buildViolation');

        $this->validator->initialize($context);
        $this->validator->validate($fieldConfigModel, new AttributeField());
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
        $builder = $this->createMock(ConstraintViolationBuilderInterface::class);
        $builder->expects($this->once())
            ->method('atPath')
            ->with('fieldName')
            ->willReturnSelf();
        $builder->expects($this->once())
            ->method('addViolation');
        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('buildViolation')
            ->with($constraint->message, ['{{ field }}' => $fieldName])
            ->willReturn($builder);

        $this->validator->initialize($context);
        $this->validator->validate($fieldConfigModel, $constraint);
    }
}
