<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\TestEntity;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    private EntityDataAccessor $entityDataAccessor;

    protected function setUp(): void
    {
        $this->entityDataAccessor = new EntityDataAccessor();
    }

    public function accessibleFieldsProvider(): array
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

    public function notAccessibleFieldsProvider(): array
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
    public function testHasGetterForAccessibleField(string $fieldName): void
    {
        self::assertTrue($this->entityDataAccessor->hasGetter(TestEntity::class, $fieldName));
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     */
    public function testHasGetterForNotAccessibleField(string $fieldName): void
    {
        self::assertFalse($this->entityDataAccessor->hasGetter(TestEntity::class, $fieldName));
    }

    /**
     * @dataProvider accessibleFieldsProvider
     */
    public function testGetValueForAccessibleField(string $fieldName): void
    {
        $entity = new TestEntity('test');
        self::assertEquals('test', $this->entityDataAccessor->getValue($entity, $fieldName));
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     */
    public function testGetValueForNotAccessibleField(string $fieldName): void
    {
        $this->expectException(\RuntimeException::class);
        $entity = new TestEntity('test');
        $this->entityDataAccessor->getValue($entity, $fieldName);
    }

    /**
     * @dataProvider accessibleFieldsProvider
     */
    public function testTryGetValueForAccessibleField(string $fieldName): void
    {
        $entity = new TestEntity('test');
        $value = 'prev';
        self::assertTrue($this->entityDataAccessor->tryGetValue($entity, $fieldName, $value));
        self::assertEquals('test', $value);
    }

    /**
     * @dataProvider notAccessibleFieldsProvider
     */
    public function testTryGetValueForNotAccessibleField(string $fieldName): void
    {
        $entity = new TestEntity('test');
        $value = 'prev';
        self::assertFalse($this->entityDataAccessor->tryGetValue($entity, $fieldName, $value));
        self::assertEquals('prev', $value);
    }

    public function testGetValueForAccessibleArrayElement(): void
    {
        $entity = ['someName' => 'test'];
        self::assertEquals('test', $this->entityDataAccessor->getValue($entity, 'someName'));
    }

    public function testGetValueForNotAccessibleArrayElement(): void
    {
        $this->expectException(\RuntimeException::class);
        $entity = ['someName' => 'test'];
        $this->entityDataAccessor->getValue($entity, 'notExistingName');
    }

    public function testTryGetValueForAccessibleArrayElement(): void
    {
        $entity = ['someName' => 'test'];
        $value = 'prev';
        self::assertTrue($this->entityDataAccessor->tryGetValue($entity, 'someName', $value));
        self::assertEquals('test', $value);
    }

    public function testTryGetValueForNotAccessibleArrayElement(): void
    {
        $entity = ['someName' => 'test'];
        $value = 'prev';
        self::assertFalse($this->entityDataAccessor->tryGetValue($entity, 'notExistingName', $value));
        self::assertEquals('prev', $value);
    }
}
