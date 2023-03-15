<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DefaultRelationField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\DefaultRelationFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class DefaultRelationFieldValidatorTest extends ConstraintValidatorTestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    protected function createValidator()
    {
        $extendConfigProvider = new ConfigProviderMock($this->createMock(ConfigManager::class), 'extend');
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

        return new DefaultRelationFieldValidator(
            new FieldNameValidationHelper(
                $extendConfigProvider,
                $this->createMock(EventDispatcherInterface::class),
                new NewEntitiesHelper(),
                (new InflectorFactory())->build()
            )
        );
    }

    /**
     * @dataProvider validateProvider
     */
    public function testValidate(
        string $fieldName,
        string $fieldType,
        ?string $expectedValidationMessageType,
        ?string $violationFieldName = null
    ) {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field = new FieldConfigModel($fieldName, $fieldType);
        $entity->addField($field);

        $constraint = new DefaultRelationField();

        $this->validator->validate($field, $constraint);

        if (null === $expectedValidationMessageType) {
            $this->assertNoViolation();
        } else {
            $message = PropertyAccess::createPropertyAccessor()
                ->getValue($constraint, $expectedValidationMessageType);
            $this->buildViolation($message)
                ->setParameters(['{{ value }}' => $fieldName, '{{ field }}' => $violationFieldName ?? $fieldName])
                ->atPath('property.path.fieldName')
                ->assertRaised();
        }
    }

    public function validateProvider(): array
    {
        return [
            ['defaultAnotherField', 'int', null],
            ['defaultMany2oneRel', 'int', null],
            ['defaultOne2ManyRel', 'int', 'duplicateRelationMessage', 'one2manyRel'],
            ['default_one_2_many_rel', 'int', 'duplicateRelationMessage', 'one2manyRel'],
            ['defaultMany2ManyRel', 'int', 'duplicateRelationMessage', 'many2manyRel'],
            ['default_many_2_many_rel', 'int', 'duplicateRelationMessage', 'many2manyRel'],
            ['defaultOne2ManyRelWithoutDefault', 'int', null],
            ['default_one_2_many_rel_without_default', 'int', null],
            ['defaultMany2ManyRelWithoutDefault', 'int', null],
            ['default_many_2_many_rel_without_default', 'int', null],
            ['field', RelationType::MANY_TO_ONE, null],
            ['field', RelationType::ONE_TO_MANY, 'duplicateFieldMessage', 'defaultField'],
            ['fieLD', RelationType::ONE_TO_MANY, 'duplicateFieldMessage', 'defaultField'],
            ['field', RelationType::MANY_TO_MANY, 'duplicateFieldMessage', 'defaultField'],
            ['fieLD', RelationType::MANY_TO_MANY, 'duplicateFieldMessage', 'defaultField'],
            ['deletedField', RelationType::ONE_TO_MANY, null],
            ['deletedField', RelationType::ONE_TO_MANY, null],
            ['toBeDeletedField', RelationType::ONE_TO_MANY, null],
            ['toBeDeletedField', RelationType::MANY_TO_MANY, null],
        ];
    }
}
