<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\MultiEnumSnapshotField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\MultiEnumSnapshotFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

class MultiEnumSnapshotFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var MultiEnumSnapshotFieldValidator */
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

        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'field' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX,
            'int'
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'deletedField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX,
            'int',
            ['is_deleted' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'toBeDeletedField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX,
            'int',
            ['state' => ExtendScope::STATE_DELETE]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'multiEnumField',
            'multiEnum'
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'enumField',
            'enum'
        );

        $this->validator = new MultiEnumSnapshotFieldValidator(
            new FieldNameValidationHelper($extendConfigProvider)
        );
    }

    /**
     * @dataProvider validateProvider
     *
     * @param string $fieldName
     * @param string $fieldType
     * @param string $expectedValidationMessageType
     */
    public function testValidate($fieldName, $fieldType, $expectedValidationMessageType)
    {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field  = new FieldConfigModel($fieldName, $fieldType);
        $entity->addField($field);

        $context = $this->getMock('Symfony\Component\Validator\Context\ExecutionContextInterface');
        $this->validator->initialize($context);

        $constraint = new MultiEnumSnapshotField();

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
            ['anotherField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX, 'int', null],
            ['enumField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX, 'int', null],
            ['multiEnumField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX, 'int', 'duplicateSnapshotMessage'],
            ['multi_enum_field_' . strtolower(ExtendHelper::ENUM_SNAPSHOT_SUFFIX), 'int', 'duplicateSnapshotMessage'],
            ['field', 'multiEnum', 'duplicateFieldMessage'],
            ['fieLD', 'multiEnum', 'duplicateFieldMessage'],
            ['deletedField', 'multiEnum', 'duplicateFieldMessage'],
            ['deleted_field', 'multiEnum', null],
            ['toBeDeletedField', 'multiEnum', 'duplicateFieldMessage'],
            ['to_be_deleted_field', 'multiEnum', null],
        ];
    }
}
