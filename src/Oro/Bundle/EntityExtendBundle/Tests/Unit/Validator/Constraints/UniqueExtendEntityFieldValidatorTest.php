<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

class UniqueExtendEntityFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $extendConfigProvider = new ConfigProviderMock(
            $configManager,
            'extend'
        );

        $extendConfigProvider->addFieldConfig(self::ENTITY_CLASS, 'activeField', 'int');
        $extendConfigProvider->addFieldConfig(self::ENTITY_CLASS, 'activeHiddenField', 'int', [], true);
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'deletedField',
            'int',
            ['is_deleted' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'toBeDeletedField',
            'int',
            ['state' => ExtendScope::STATE_DELETE]
        );

        $this->validator = new UniqueExtendEntityFieldValidator(
            new FieldNameValidationHelper($extendConfigProvider)
        );
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param string $expectedValidationMessageType
     */
    public function testValidate($fieldName, $expectedValidationMessageType)
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);

        $constraint = new UniqueExtendEntityField();

        if ($expectedValidationMessageType) {
            $message   = PropertyAccess::createPropertyAccessor()
                ->getValue($constraint, $expectedValidationMessageType);
            $violation = $this->getMock('Symfony\Component\Validator\Violation\ConstraintViolationBuilderInterface');
            $context->expects($this->once())
                ->method('buildViolation')
                ->with($message)
                ->willReturn($violation);
            $violation->expects($this->once())
                ->method('atPath')
                ->with('fieldName')
                ->willReturnSelf();
            $violation->expects($this->once())
                ->method('addViolation');
        } else {
            $context->expects($this->never())
                ->method('buildViolation');
        }

        $this->validator->validate($field, $constraint);
    }

    public function validateProvider()
    {
        return [
            ['id', 'sameFieldMessage'],
            ['i_d', 'similarFieldMessage'],
            ['anotherField', null],
            ['activeField', 'sameFieldMessage'],
            ['active_field', 'similarFieldMessage'],
            ['activeHiddenField', 'sameFieldMessage'],
            ['active_hidden_field', 'similarFieldMessage'],
            ['deletedField', 'sameFieldMessage'],
            ['deleted_field', null],
            ['toBeDeletedField', 'sameFieldMessage'],
            ['to_be_deleted_field', null],
        ];
    }
}
