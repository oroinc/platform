<?php

namespace Oro\Component\EntitySerializer\Tests\Unit;

use Oro\Component\EntitySerializer\EntityDataAccessor;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\TestEntity;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\TestEntityWithArrayAccess;
use Oro\Component\EntitySerializer\Tests\Unit\Fixtures\Entity\TestEntityWithMagicMethods;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class EntityDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    protected EntityDataAccessor $entityDataAccessor;

    protected function setUp(): void
    {
        $this->entityDataAccessor = $this->createEntityDataAccessor();
    }

    protected function createEntityDataAccessor(): EntityDataAccessor
    {
        return new EntityDataAccessor();
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
            ['publicCanAccessor'],
            ['public_can_accessor'],
            ['publicGetSetter'],
            ['public_get_setter'],
            ['valueGetter'],
            ['value_getter'],
            ['valueGetGetter'],
            ['value_get_getter'],
            ['valueIsGetter'],
            ['value_is_getter'],
            ['valueHasGetter'],
            ['value_has_getter'],
            ['valueCanGetter'],
            ['value_can_getter'],
            ['publicBaseProperty'],
            ['protectedBaseProperty'],
            ['privateBaseProperty'],
            ['baseValueGetter'],
            ['base_value_getter'],
            ['baseValueGetGetter'],
            ['base_value_get_getter'],
            ['baseValueIsGetter'],
            ['base_value_is_getter'],
            ['baseValueHasGetter'],
            ['base_value_has_getter'],
            ['baseValueCanGetter'],
            ['base_value_can_getter'],
        ];
    }

    public function notAccessibleFieldsProvider(): array
    {
        return [
            ['undefinedProperty'],
            ['protectedAccessor'],
            ['protectedIsAccessor'],
            ['protectedHasAccessor'],
            ['protectedCanAccessor'],
            ['privateAccessor'],
            ['privateIsAccessor'],
            ['privateHasAccessor'],
            ['privateCanAccessor'],
            ['publicAccessorWithParameter'],
            ['publicIsAccessorWithParameter'],
            ['publicHasAccessorWithParameter'],
            ['publicCanAccessorWithParameter'],
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
    }

    public function testHasGetterForAttributeWhenClassDoesNotImplementArrayAccess(): void
    {
        $className = \stdClass::class;
        $propertyName = 'testAttribute';
        self::assertFalse($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testTryGetValueForAttributeWhenClassDoesNotImplementArrayAccess(): void
    {
        $object = new TestEntity();
        $propertyName = 'testAttribute';
        $value = 'prev';
        self::assertFalse($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertEquals('prev', $value);
    }

    public function testHasGetterForAttributeThatHasGetterWhenClassImplementsArrayAccess(): void
    {
        $className = TestEntityWithArrayAccess::class;
        $propertyName = 'typedAttribute';
        self::assertTrue($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testHasGetterForAttributeWhenClassImplementsArrayAccess(): void
    {
        $className = TestEntityWithArrayAccess::class;
        $propertyName = 'testAttribute';
        self::assertFalse($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testTryGetValueForAttributeThatHasGetterWhenClassImplementsArrayAccess(): void
    {
        $object = new TestEntityWithArrayAccess();
        $object->setTypedAttribute('test');
        $propertyName = 'typedAttribute';
        $value = null;
        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertSame('test', $value);
    }

    public function testTryGetValueForAttributeWhenClassImplementsArrayAccess(): void
    {
        $object = new TestEntityWithArrayAccess();
        $propertyName = 'testAttribute';
        $object[$propertyName] = 'test';
        $value = null;
        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertSame('test', $value);
    }

    public function testTryGetValueForUnknownAttributeWhenClassImplementsArrayAccess(): void
    {
        $object = new TestEntityWithArrayAccess();
        $propertyName = 'anotherAttribute';
        $value = null;
        self::assertFalse($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertNull($value);
    }

    public function testHasGetterForAttributeThatHasGetterWhenClassHasMagicMethods(): void
    {
        $className = TestEntityWithMagicMethods::class;
        $propertyName = 'typedAttribute';
        self::assertTrue($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testHasGetterForAttributeWhenClassHasMagicMethods(): void
    {
        $className = TestEntityWithMagicMethods::class;
        $propertyName = 'magicAttribute';
        self::assertTrue($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testHasGetterForUnknownAttributeWhenClassHasMagicMethods(): void
    {
        $className = TestEntityWithMagicMethods::class;
        $propertyName = 'anotherAttribute';
        self::assertFalse($this->entityDataAccessor->hasGetter($className, $propertyName));
    }

    public function testTryGetValueForAttributeThatHasGetterWhenClassHasMagicMethods(): void
    {
        $object = new TestEntityWithMagicMethods();
        $object->setTypedAttribute('test');
        $propertyName = 'typedAttribute';
        $value = null;
        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertSame('test', $value);
    }

    public function testTryGetValueForAttributeWhenClassHasMagicMethods(): void
    {
        $object = new TestEntityWithMagicMethods();
        $propertyName = 'magicAttribute';
        $object->{$propertyName} = 'test';
        $value = null;
        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertSame('test', $value);
    }

    public function testTryGetValueForUnknownAttributeWhenClassHasMagicMethods(): void
    {
        $object = new TestEntityWithMagicMethods();
        $propertyName = 'anotherAttribute';
        $value = null;
        self::assertFalse($this->entityDataAccessor->tryGetValue($object, $propertyName, $value));
        self::assertNull($value);
    }
}
