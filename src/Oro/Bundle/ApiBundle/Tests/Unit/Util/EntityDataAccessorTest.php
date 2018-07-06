<?php

namespace Oro\Bundle\ApiBundle\Tests\Unit\Util;

use Oro\Bundle\ApiBundle\Model\EntityIdentifier;
use Oro\Bundle\ApiBundle\Util\EntityDataAccessor;

class EntityDataAccessorTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityDataAccessor */
    private $entityDataAccessor;

    protected function setUp()
    {
        $this->entityDataAccessor = new EntityDataAccessor();
    }

    public function testHasGetterForProperty()
    {
        $className = EntityIdentifier::class;
        $property = 'id';

        self::assertTrue($this->entityDataAccessor->hasGetter($className, $property));
    }

    public function testHasGetterForAttributeWhenClassImplementsArrayAccess()
    {
        $className = EntityIdentifier::class;
        $property = 'testAttribute';

        self::assertTrue($this->entityDataAccessor->hasGetter($className, $property));
    }

    public function testHasGetterForAttributeWhenClassDoesNotImplementArrayAccess()
    {
        $className = \stdClass::class;
        $property = 'testAttribute';

        self::assertFalse($this->entityDataAccessor->hasGetter($className, $property));
    }

    public function testTryGetValueForProperty()
    {
        $object = new EntityIdentifier(123);
        $property = 'id';
        $value = null;

        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $property, $value));
        self::assertSame(123, $value);
    }

    public function testTryGetValueForAttributeWhenClassImplementsArrayAccess()
    {
        $object = new EntityIdentifier();
        $property = 'testAttribute';
        $value = null;

        $object[$property] = 'test';

        self::assertTrue($this->entityDataAccessor->tryGetValue($object, $property, $value));
        self::assertSame('test', $value);
    }

    public function testTryGetValueForUnknownAttributeWhenClassImplementsArrayAccess()
    {
        $object = new EntityIdentifier();
        $property = 'testAttribute';
        $value = null;

        self::assertFalse($this->entityDataAccessor->tryGetValue($object, $property, $value));
        self::assertNull($value);
    }

    public function testTryGetValueForAttributeWhenClassDoesNotImplementArrayAccess()
    {
        $object = new EntityIdentifier();
        $property = 'testAttribute';
        $value = null;

        self::assertFalse($this->entityDataAccessor->tryGetValue($object, $property, $value));
        self::assertNull($value);
    }
}
