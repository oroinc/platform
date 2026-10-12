<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Tests\Unit\Matcher;

use Oro\Component\Duplicator\Matcher\PropertyMatcher;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\PropertyBagInterface;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity2;
use PHPUnit\Framework\TestCase;

class PropertyMatcherTest extends TestCase
{
    private PropertyMatcher $matcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->matcher = new PropertyMatcher(Entity1::class, 'extended_field');
    }

    public function testMatchesBagByOwnerClass(): void
    {
        $bag = new PropertyBag(Entity1::class, ['extended_field' => 'value']);

        // the bag is not an instance of the owner class, the owner class decides
        self::assertNotInstanceOf(Entity1::class, $bag);
        self::assertTrue($this->matcher->matches($bag, 'extended_field'));
    }

    public function testMatchesBagWhenOwnerIsSubclass(): void
    {
        $child = $this->createEntity1Child();

        self::assertTrue($this->matcher->matches(new PropertyBag($child::class, []), 'extended_field'));
    }

    public function testDoesNotMatchBagWithUnrelatedOwner(): void
    {
        self::assertFalse($this->matcher->matches(new PropertyBag(Entity2::class, []), 'extended_field'));
    }

    public function testDoesNotMatchBagWithUnknownOwnerClass(): void
    {
        self::assertFalse($this->matcher->matches(new PropertyBag('Not\Existing', []), 'extended_field'));
    }

    public function testDoesNotMatchBagWhenPropertyNameDiffers(): void
    {
        self::assertFalse($this->matcher->matches(new PropertyBag(Entity1::class, []), 'other_field'));
    }

    public function testMatchesAnyPropertyBagImplementation(): void
    {
        $bag = $this->createMock(PropertyBagInterface::class);
        $bag->expects(self::once())
            ->method('getOwnerClass')
            ->willReturn(Entity1::class);

        self::assertTrue($this->matcher->matches($bag, 'extended_field'));
    }

    public function testMatchesPlainObjectByInstanceOf(): void
    {
        $child = $this->createEntity1Child();

        self::assertTrue($this->matcher->matches(new Entity1(1), 'extended_field'));
        self::assertTrue($this->matcher->matches($child, 'extended_field'));
    }

    public function testDoesNotMatchPlainObjectOfAnotherClass(): void
    {
        self::assertFalse($this->matcher->matches(new Entity2(), 'extended_field'));
        self::assertFalse($this->matcher->matches(new Entity1(1), 'other_field'));
    }

    public function testDoesNotMatchStdClass(): void
    {
        $object = new \stdClass();
        $object->extended_field = 'value';

        self::assertFalse($this->matcher->matches($object, 'extended_field'));
    }

    /** No stub extends another one; the subclass is built on the fly. */
    private function createEntity1Child(): Entity1
    {
        return new class (1) extends Entity1 {
        };
    }
}
