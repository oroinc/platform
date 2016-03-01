<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DefaultRelationField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DefaultRelationFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;

class DefaultRelationFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    const ENTITY_CLASS = 'Test\Entity';

    /** @var DefaultRelationFieldValidator */
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

        $extendConfigProvider->addFieldConfig(self::ENTITY_CLASS, 'defaultField', 'int');
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'defaultDeletedField',
            'int',
            ['is_deleted' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'defaultToBeDeletedField',
            'int',
            ['state' => ExtendScope::STATE_DELETE]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'many2oneRel',
            RelationType::MANY_TO_ONE
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'one2manyRel',
            RelationType::ONE_TO_MANY
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'many2manyRel',
            RelationType::MANY_TO_MANY
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'one2manyRelWithoutDefault',
            RelationType::ONE_TO_MANY,
            ['without_default' => true]
        );
        $extendConfigProvider->addFieldConfig(
            self::ENTITY_CLASS,
            'many2manyRelWithoutDefault',
            RelationType::MANY_TO_MANY,
            ['without_default' => true]
        );

        $this->validator = new DefaultRelationFieldValidator(
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

        $constraint = new DefaultRelationField();

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
            ['defaultAnotherField', 'int', null],
            ['defaultMany2oneRel', 'int', null],
            ['defaultOne2ManyRel', 'int', 'duplicateRelationMessage'],
            ['default_one_2_many_rel', 'int', 'duplicateRelationMessage'],
            ['defaultMany2ManyRel', 'int', 'duplicateRelationMessage'],
            ['default_many_2_many_rel', 'int', 'duplicateRelationMessage'],
            ['defaultOne2ManyRelWithoutDefault', 'int', null],
            ['default_one_2_many_rel_without_default', 'int', null],
            ['defaultMany2ManyRelWithoutDefault', 'int', null],
            ['default_many_2_many_rel_without_default', 'int', null],
            ['field', RelationType::MANY_TO_ONE, null],
            ['field', RelationType::ONE_TO_MANY, 'duplicateFieldMessage'],
            ['fieLD', RelationType::ONE_TO_MANY, 'duplicateFieldMessage'],
            ['field', RelationType::MANY_TO_MANY, 'duplicateFieldMessage'],
            ['fieLD', RelationType::MANY_TO_MANY, 'duplicateFieldMessage'],
            ['deletedField', RelationType::ONE_TO_MANY, null],
            ['deletedField', RelationType::ONE_TO_MANY, null],
            ['toBeDeletedField', RelationType::ONE_TO_MANY, null],
            ['toBeDeletedField', RelationType::MANY_TO_MANY, null],
        ];
    }
}
