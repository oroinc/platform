<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueExtendEntityFieldValidatorTest extends ConstraintValidatorTestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    protected function createValidator()
    {
        $extendConfigProvider = new ConfigProviderMock($this->createMock(ConfigManager::class), 'extend');
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

        return new UniqueExtendEntityFieldValidator(
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
        ?string $expectedValidationMessageType,
        ?string $violationFieldName = null
    ) {
        $entity = new EntityConfigModel(self::ENTITY_CLASS);
        $field = new FieldConfigModel($fieldName);
        $entity->addField($field);

        $constraint = new UniqueExtendEntityField();

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
            ['id', 'sameFieldMessage'],
            ['i_d', 'similarFieldMessage', 'id'],
            ['anotherField', null],
            ['activeField', 'sameFieldMessage'],
            ['active_field', 'similarFieldMessage', 'activeField'],
            ['activeHiddenField', 'sameFieldMessage'],
            ['active_hidden_field', 'similarFieldMessage', 'activeHiddenField'],
            ['deletedField', 'sameFieldMessage'],
            ['deleted_field', null],
            ['toBeDeletedField', 'sameFieldMessage'],
            ['to_be_deleted_field', null, null],
        ];
    }
}
