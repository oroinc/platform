<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ChangeTypeExtendEntityField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ChangeTypeExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class ChangeTypeExtendEntityFieldValidatorTest extends ConstraintValidatorTestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    /** @var FieldNameValidationHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldNameValidationHelper;

    protected function setUp(): void
    {
        $this->fieldNameValidationHelper = $this->createMock(FieldNameValidationHelper::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new ChangeTypeExtendEntityFieldValidator($this->fieldNameValidationHelper);
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(
        array $fieldData,
        ?string $expectedValidationMessage,
        ?string $violationFieldName = null
    ) {
        $name = 'testField';
        $type = 'string';

        $field = new FieldConfigModel($name, $type);

        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $entity->addField($field);

        $this->fieldNameValidationHelper->expects($this->once())
            ->method('getSimilarExistingFieldData')
            ->with(self::ENTITY_CLASS, $name)
            ->willReturn($fieldData);

        $constraint = new ChangeTypeExtendEntityField();

        $this->validator->validate($field, $constraint);

        if (null === $expectedValidationMessage) {
            $this->assertNoViolation();
        } else {
            $this->buildViolation($expectedValidationMessage)
                ->setParameters([
                    '{{ value }}' => $violationFieldName ?? $fieldData[0],
                    '{{ field }}' => $fieldData[0]
                ])
                ->atPath('property.path.fieldName')
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            [['testField', 'int'], 'oro.entity_extend.change_type_not_allowed.message'],
            [['test_field', 'string'], 'oro.entity_extend.change_type_not_allowed.message', 'testField'],
            [['testField', 'string'], null],
            [[], null],
        ];
    }
}
