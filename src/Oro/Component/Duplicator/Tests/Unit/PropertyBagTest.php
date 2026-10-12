<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Tests\Unit;

use DeepCopy\Reflection\ReflectionHelper;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use PHPUnit\Framework\TestCase;

class PropertyBagTest extends TestCase
{
    public function testDataExposedAsDynamicProperties(): void
    {
        $relation = new \stdClass();
        $bag = new PropertyBag(Entity1::class, ['string_field' => 'value', 'relation' => $relation]);

        self::assertSame('value', $bag->string_field);
        self::assertSame($relation, $bag->relation);
    }

    public function testPropertiesVisibleToReflection(): void
    {
        $bag = new PropertyBag(Entity1::class, ['string_field' => 'value', 'null_field' => null]);

        $names = array_map(
            static fn (\ReflectionProperty $property) => $property->getName(),
            (new \ReflectionObject($bag))->getProperties()
        );
        self::assertContains('string_field', $names);
        self::assertContains('null_field', $names);
        self::assertSame('value', ReflectionHelper::getProperty($bag, 'string_field')->getValue($bag));
    }

    public function testGetOwnerClass(): void
    {
        self::assertSame(Entity1::class, (new PropertyBag(Entity1::class, []))->getOwnerClass());
        self::assertSame('Not\Existing', (new PropertyBag('Not\Existing', []))->getOwnerClass());
    }

    public function testToArrayReturnsDataWithoutOwnerClass(): void
    {
        $relation = new \stdClass();
        $data = ['string_field' => 'value', 'null_field' => null, 'relation' => $relation];

        $array = (new PropertyBag(Entity1::class, $data))->toArray();

        self::assertSame($data, $array);
        self::assertArrayNotHasKey('ownerClass', $array);
    }

    public function testToArrayReflectsChangedProperties(): void
    {
        $bag = new PropertyBag(Entity1::class, ['string_field' => 'value', 'removed' => 1]);

        $bag->string_field = null;
        $bag->added = 2;
        unset($bag->removed);

        self::assertSame(['string_field' => null, 'added' => 2], $bag->toArray());
    }

    public function testEmptyData(): void
    {
        self::assertSame([], (new PropertyBag(Entity1::class, []))->toArray());
    }

    public function testOwnerClassIsAnOrdinaryEntryName(): void
    {
        $bag = new PropertyBag(Entity1::class, ['ownerClass' => 'value']);

        self::assertSame(Entity1::class, $bag->getOwnerClass());
        self::assertSame(['ownerClass' => 'value'], $bag->toArray());
    }
}
