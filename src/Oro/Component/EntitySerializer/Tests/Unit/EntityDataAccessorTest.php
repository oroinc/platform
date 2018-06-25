<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\TestEntity;

class EntityDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityDataAccessor */
    protected $entityDataAccessor;

    protected function setUp()
    {
        $this->entityDataAccessor = new EntityDataAccessor();
    }

    public function accessibleFieldsProvider()
    {
        return [
            ['publicProperty'],
            ['protectedProperty'],
            ['privateProperty'],
            ['publicAccessor'],
            ['public_accessor'],
            ['publicIsAccessor'],
            ['public_is_accessor'],
            ['publicHasAccessor'],
            ['public_has_accessor'],
            ['publicGetSetter'],
            ['public_get_setter'],
        ];
    }

    public function notAccessibleFieldsProvider()
    {
        return [
            ['undefinedProperty'],
            ['protectedAccessor'],
            ['protectedIsAccessor'],
            ['protectedHasAccessor'],
            ['privateAccessor'],
            ['privateIsAccessor'],
            ['privateHasAccessor'],
            ['publicAccessorWithParameter'],
            ['publicIsAccessorWithParameter'],
            ['publicHasAccessorWithParameter'],
        ];
    }

    /**
     * @dataProvider accessibleFieldsProvider
     */
    public function testHasGetterForAccessibleField($fieldName)
    {
        self::assertTrue($this->entityDataAccessor->hasGetter(TestEntity::class, $fieldName));
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     */
    public function testHasGetterForNotAccessibleField($fieldName)
    {
        self::assertFalse($this->entityDataAccessor->hasGetter(TestEntity::class, $fieldName));
    }

    /**
     * @dataProvider accessibleFieldsProvider
     */
    public function testGetValueForAccessibleField($fieldName)
    {
        $entity = new TestEntity('test');
        self::assertEquals('test', $this->entityDataAccessor->getValue($entity, $fieldName));
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     * @expectedException \RuntimeException
     */
    public function testGetValueForNotAccessibleField($fieldName)
    {
        $entity = new TestEntity('test');
        $this->entityDataAccessor->getValue($entity, $fieldName);
    }

    /**
     * @dataProvider accessibleFieldsProvider
     */
    public function testTryGetValueForAccessibleField($fieldName)
    {
        $entity = new TestEntity('test');
        $value = 'prev';
        self::assertTrue($this->entityDataAccessor->tryGetValue($entity, $fieldName, $value));
        self::assertEquals('test', $value);
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     */
    public function testTryGetValueForNotAccessibleField($fieldName)
    {
        $entity = new TestEntity('test');
        $value = 'prev';
        self::assertFalse($this->entityDataAccessor->tryGetValue($entity, $fieldName, $value));
        self::assertEquals('prev', $value);
    }

    public function testGetValueForAccessibleArrayElement()
    {
        $entity = ['someName' => 'test'];
        self::assertEquals('test', $this->entityDataAccessor->getValue($entity, 'someName'));
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testGetValueForNotAccessibleArrayElement()
    {
        $entity = ['someName' => 'test'];
        $this->entityDataAccessor->getValue($entity, 'notExistingName');
    }

    public function testTryGetValueForAccessibleArrayElement()
    {
        $entity = ['someName' => 'test'];
        $value = 'prev';
        self::assertTrue($this->entityDataAccessor->tryGetValue($entity, 'someName', $value));
        self::assertEquals('test', $value);
    }

    public function testTryGetValueForNotAccessibleArrayElement()
    {
        $entity = ['someName' => 'test'];
        $value = 'prev';
        self::assertFalse($this->entityDataAccessor->tryGetValue($entity, 'notExistingName', $value));
        self::assertEquals('prev', $value);
    }
}
