<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\MultiEnumSnapshotField;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\MultiEnumSnapshotFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MultiEnumSnapshotFieldValidatorTest extends ConstraintValidatorTestCase
{
    private const ENTITY_CLASS = 'Test\Entity';

    protected function createValidator()
    {
        $extendConfigProvider = new ConfigProviderMock($this->createMock(ConfigManager::class), 'extend');
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

        return new MultiEnumSnapshotFieldValidator(
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

        $constraint = new MultiEnumSnapshotField();

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
            ['anotherField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX, 'int', null],
            ['enumField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX, 'int', null],
            [
                'multiEnumField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX,
                'int',
                'duplicateSnapshotMessage',
                'multiEnumField'
            ],
            [
                'multi_enum_field_' . strtolower(ExtendHelper::ENUM_SNAPSHOT_SUFFIX),
                'int',
                'duplicateSnapshotMessage',
                'multiEnumField'
            ],
            [
                'field',
                'multiEnum',
                'duplicateFieldMessage',
                'field' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX
            ],
            [
                'fieLD',
                'multiEnum',
                'duplicateFieldMessage',
                'field' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX
            ],
            [
                'deletedField',
                'multiEnum',
                'duplicateFieldMessage',
                'deletedField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX
            ],
            ['deleted_field', 'multiEnum', null],
            [
                'toBeDeletedField',
                'multiEnum',
                'duplicateFieldMessage',
                'toBeDeletedField' . ExtendHelper::ENUM_SNAPSHOT_SUFFIX
            ],
            ['to_be_deleted_field', 'multiEnum', null],
        ];
    }
}
