<?php

namespace Oro\Bundle\EntityExtendBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Tests\Unit\ConfigProviderMock;
use Oro\Bundle\EntityExtendBundle\Tools\ClassMethodNameChecker;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityFieldValidator;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodName;
use Oro\Bundle\EntityExtendBundle\Validator\Constraints\UniqueExtendEntityMethodNameValidator;
use Oro\Bundle\EntityExtendBundle\Validator\FieldNameValidationHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class UniqueExtendEntityMethodNameValidatorTest extends ConstraintValidatorTestCase
{
    const TEST_CLASS_NAME = 'Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\Tools\TestEntity';
    const TEST_FIELD_NAME = 'testField';

    /** @var \PHPUnit\Framework\MockObject\MockObject */
    protected $classMethodNameChecker;

    /** @var UniqueExtendEntityFieldValidator */
    protected $validator;

    protected function setUp()
    {
        $this->classMethodNameChecker = $this->getMockBuilder(ClassMethodNameChecker::class)
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();

        $this->setPropertyPath(null);
    }

    protected function createValidator()
    {
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher */
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        return new UniqueExtendEntityMethodNameValidator(
            new FieldNameValidationHelper(
                new ConfigProviderMock($configManager, 'extend'),
                $eventDispatcher,
                new NewEntitiesHelper()
            ),
            $this->classMethodNameChecker
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

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel supported only, string given
     */
    public function testAssertValidatingValue()
    {
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

    public function relationTypeProvider()
    {
        return [
            ['oneToOne'],
            ['oneToMany'],
            ['manyToOne'],
            ['manyToMany'],
        ];
    }

    /**
     * @dataProvider relationTypeProvider
     */
    public function testRelationMethodsDoNotExist($relationType)
    {
        $field = $this->getFieldConfigModel($relationType);
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
     */
    public function testRelationMethodsExist($relationType)
    {
        $field = $this->getFieldConfigModel($relationType);
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
