<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Inflector\Rules\English\InflectorFactory;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
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
    const TEST_CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity';
    const TEST_FIELD_NAME = 'testField';

    /** @var ClassMethodNameChecker|\PHPUnit\Framework\MockObject\MockObject */
    protected $classMethodNameChecker;

    /** @var FieldTypeHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $fieldTypeHelper;

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp(): void
    {
        $this->classMethodNameChecker = $this->createMock(ClassMethodNameChecker::class);
        $this->fieldTypeHelper = $this->createMock(FieldTypeHelper::class);

        parent::setUp();

        $this->setPropertyPath(null);
    }

    protected function createValidator()
    {
        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
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
    protected function getFieldConfigModel($fieldType)
    {
        $entity = new EntityConfigModel(self::TEST_CLASS_NAME);
        $field = new FieldConfigModel(self::TEST_FIELD_NAME, $fieldType);
        $entity->addField($field);

        return $field;
    }

    public function testAssertValidatingValue()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, string given'
        );

        $field = '';
        $constraint = new UniqueExtendEntityMethodName();

        $this->validator->validate($field, $constraint);
    }

    public function testForUndefinedEntity()
    {
        $entity = new EntityConfigModel('Test\SomeUndefinedClass');
        $field = new FieldConfigModel('testField', 'string');
        $entity->addField($field);
        $constraint = new UniqueExtendEntityMethodName();

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testFieldDoesNotExist()
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$setters, []],
            ]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testGetterExists()
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::once())
            ->method('getMethods')
            ->with(self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$getters)
            ->willReturn(['get']);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function testSetterExists()
    {
        $field = $this->getFieldConfigModel('string');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$setters, ['set']],
            ]);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    /**
     * @return array
     */
    public function relationTypeProvider()
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
     * @param string $fieldType
     * @param string $relationType
     */
    public function testRelationMethodsDoNotExist($fieldType, $relationType)
    {
        $this->fieldTypeHelper->expects($this->any())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn($relationType);
        $field = $this->getFieldConfigModel($fieldType);
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(3))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$setters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$relationMethods, []],
            ]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @dataProvider relationTypeProvider
     * @param string $fieldType
     * @param string $relationType
     */
    public function testRelationMethodsExist($fieldType, $relationType)
    {
        $this->fieldTypeHelper->expects($this->any())
            ->method('getUnderlyingType')
            ->with($fieldType)
            ->willReturn($relationType);
        $field = $this->getFieldConfigModel($fieldType);
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(3))
            ->method('getMethods')
            ->willReturnMap([
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$getters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$setters, []],
                [self::TEST_FIELD_NAME, self::TEST_CLASS_NAME, ClassMethodNameChecker::$relationMethods, ['add']],
            ]);

        $this->validator->validate($field, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['{{ value }}' => 'testField', '{{ field }}' => ''])
            ->atPath('fieldName')
            ->assertRaised();
    }

    public function testForUnsupportedCombinedType()
    {
        $field = $this->getFieldConfigModel('item1||item2');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::exactly(2))
            ->method('getMethods')
            ->willReturn([]);

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToOne()
    {
        $field = $this->getFieldConfigModel('manyToOne|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenNoRelationConfig()
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseManyToManyWhenNoTargetFieldConfig()
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

    public function testReuseManyToManyForOwningSideRelationConfig()
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, self::TEST_FIELD_NAME)
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

    public function testReuseManyToManyWhenFieldNameEqualsToExpectedFieldName()
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, self::TEST_FIELD_NAME)
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

    public function testReuseManyToManyWhenFieldNameNotEqualToExpectedFieldName()
    {
        $field = $this->getFieldConfigModel('manyToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'manyToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, 'expectedField')
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

    public function testReuseOneToManyWhenNoRelationConfig()
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $constraint = new UniqueExtendEntityMethodName();

        $this->classMethodNameChecker->expects(self::never())
            ->method('getMethods');

        $this->validator->validate($field, $constraint);

        $this->assertNoViolation();
    }

    public function testReuseOneToManyWhenNoTargetFieldConfig()
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

    public function testReuseOneToManyForInverseSideRelationConfig()
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => false,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, self::TEST_FIELD_NAME)
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

    public function testReuseOneToManyWhenFieldNameEqualsToExpectedFieldName()
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, self::TEST_FIELD_NAME)
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

    public function testReuseOneToManyWhenFieldNameNotEqualToExpectedFieldName()
    {
        $field = $this->getFieldConfigModel('oneToMany|Source|Target|field||');
        $field->getEntity()->fromArray(
            'extend',
            [
                'relation' => [
                    'oneToMany|Source|Target|field' => [
                        'owner'    => true,
                        'field_id' => new FieldConfigId('extend', self::TEST_CLASS_NAME, 'expectedField')
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
