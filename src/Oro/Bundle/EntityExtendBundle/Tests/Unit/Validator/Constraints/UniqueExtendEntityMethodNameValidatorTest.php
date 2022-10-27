<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodName;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodNameValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class UniqueExtendEntityMethodNameValidatorTest extends ConstraintValidatorTestCase
{
    private const TEST_FIELD_NAME = 'testField';

    /** @var ClassMethodNameChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $classMethodNameChecker;

    /** @var FieldTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $fieldTypeHelper;

    protected function setUp(): void
    {
        $this->classMethodNameChecker = $this->createMock(ClassMethodNameChecker::class);
        $this->fieldTypeHelper = $this->createMock(FieldTypeHelper::class);

        parent::setUp();

        $this->setPropertyPath('');
    }

    protected function createValidator(): UniqueExtendEntityMethodNameValidator
    {
        $configManager = $this->createMock(ConfigManager::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new UniqueExtendEntityMethodNameValidator(
            new FieldNameValidationHelper(
                new ConfigProviderMock($configManager, 'extend'),
                $eventDispatcher,
                new NewEntitiesHelper(),
                (new InflectorFactory())->build()
            ),
            $this->classMethodNameChecker,
            $this->fieldTypeHelper
        );
    }

    /**
     * @param string $fieldType
     *
     * @return FieldConfigModel
     */
    protected function getFieldConfigModel(string $fieldType): FieldConfigModel
    {
        $entity = new EntityConfigModel(TestEntity::class);
        $field = new FieldConfigModel(self::TEST_FIELD_NAME, $fieldType);
        $entity->addField($field);

        return $field;
    }

    public function testAssertValidatingValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, string given'
        );

        $field = '';
        $constraint = new UniqueExtendEntityMethodName();

        $this->validator->validate($field, $constraint);
    }

    public function testForUndefinedEntity(): void
    {
        $entity = new EntityConfigModel('Test\SomeUndefinedClass');
        $field = new FieldConfigModel('testField', 'string');
        $entity->addField($field);
        $constraint = new UniqueExtendEntityMethodName();

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testFieldDoesNotExist(): void
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$setters, []],
            ]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testGetterExists(): void
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::once())
            ->method('getMethods')
            ->with(self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$getters)
            ->willReturn(['get']);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function testSetterExists(): void
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$setters, ['set']],
            ]);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function relationTypeProvider(): array
    {
        return [
            ['oneToOne', 'oneToOne'],
            ['oneToMany', 'oneToMany'],
            ['manyToOne', 'manyToOne'],
            ['manyToMany', 'manyToMany'],
            ['enum', 'manyToOne'],
            ['multiEnum', 'manyToMany']
        ];
    }

    /**
     * @dataProvider relationTypeProvider
     */
    public function testRelationMethodsDoNotExist(string $fieldType, string $relationType): void
    {
        $this->fieldTypeHelper->expects(self::any())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn($relationType);
        $field = $this->getFieldConfigModel($fieldType);
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(3))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$setters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$relationMethods, []],
            ]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider relationTypeProvider
     */
    public function testRelationMethodsExist(string $fieldType, string $relationType): void
    {
        $this->fieldTypeHelper->expects(self::any())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn($relationType);
        $field = $this->getFieldConfigModel($fieldType);
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(3))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$setters, []],
                [self::TEST_FIELD_NAME, TestEntity::class, ClassMethodNameChecker::$relationMethods, ['add']],
            ]);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function testForUnsupportedCombinedType(): void
    {
        $field = $this->getFieldConfigModel('item1||item2');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturn([]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToOne(): void
    {
        $field = $this->getFieldConfigModel('manyToOne|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenNoRelationConfig(): void
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenNoTargetFieldConfig(): void
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner' => false
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyForOwningSideRelationConfig(): void
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, self::TEST_FIELD_NAME)
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenFieldNameEqualsToExpectedFieldName(): void
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, self::TEST_FIELD_NAME)
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenFieldNameNotEqualToExpectedFieldName(): void
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, 'expectedField')
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->unexpectedNameMessage)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => 'expectedField'])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function testReuseOneToManyWhenNoRelationConfig(): void
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseOneToManyWhenNoTargetFieldConfig(): void
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner' => true
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseOneToManyForInverseSideRelationConfig(): void
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, self::TEST_FIELD_NAME)
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseOneToManyWhenFieldNameEqualsToExpectedFieldName(): void
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, self::TEST_FIELD_NAME)
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseOneToManyWhenFieldNameNotEqualToExpectedFieldName(): void
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', TestEntity::class, 'expectedField')
                    ]
                ]
            ]
        );
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->unexpectedNameMessage)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => 'expectedField'])
            ->atPath('fieldName')
            ->assertRaised();
    }
}
