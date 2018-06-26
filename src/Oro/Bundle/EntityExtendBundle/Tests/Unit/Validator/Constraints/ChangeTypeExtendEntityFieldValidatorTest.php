<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ChangeTypeExtendEntityField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\ChangeTypeExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface;

class ChangeTypeExtendEntityFieldValidatorTest extends \PHPUnit\Framework\TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var FieldNameValidationHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldNameValidationHelper;

    /** @var ChangeTypeExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $this->fieldNameValidationHelper = $this->createMock(FieldNameValidationHelper::class);

        $this->validator = new ChangeTypeExtendEntityFieldValidator($this->fieldNameValidationHelper);
    }

    /**
     * @dataProvider validateProvider
     *
     * @param array $fielData
     * @param string $expectedValidationMessage
     */
    public function testValidate(array $fielData, $expectedValidationMessage)
    {
        $name = 'testField';
        $type = 'string';

        $field = new FieldConfigModel($name, $type);

        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $entity->addField($field);

        $this->fieldNameValidationHelper->expects($this->once())
            ->method('getSimilarExistingFieldData')
            ->with(self::ENTITY_CLASS, $name)
            ->willReturn($fielData);

        /** @var ExecutionContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(ExecutionContextInterface::class);

        $this->validator->initialize($context);

        $constraint = new ChangeTypeExtendEntityField();

        if ($expectedValidationMessage) {
            $violation = $this->createMock(ConstraintViolationBuilderInterface::class);
            $violation->expects($this->once())
                ->method('atPath')
                ->with('fieldName')
                ->willReturnSelf();
            $violation->expects($this->once())
                ->method('addViolation');

            $context->expects($this->once())
                ->method('buildViolation')
                ->with($expectedValidationMessage)
                ->willReturn($violation);
        } else {
            $context->expects($this->never())
                ->method('buildViolation');
        }

        $this->validator->validate($field, $constraint);
    }

    /**
     * @return array
     */
    public function validateProvider()
    {
        return [
            [['testField', 'int'], 'oro.entity_extend.change_type_not_allowed.message'],
            [['test_field', 'string'], 'oro.entity_extend.change_type_not_allowed.message'],
            [['testField', 'string'], null],
            [[], null],
        ];
    }
}
