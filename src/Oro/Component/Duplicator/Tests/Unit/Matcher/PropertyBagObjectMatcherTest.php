<?php

declare(strict_types=1);

namespace Oro\Component\Duplicator\Tests\Unit\Matcher;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\Duplicator\Matcher\PropertyBagObjectMatcher;
use Oro\Component\Duplicator\PropertyBag;
use Oro\Component\Duplicator\PropertyBagInterface;
use Oro\Component\Duplicator\Tests\Unit\Stub\Entity1;
use PHPUnit\Framework\TestCase;

class PropertyBagObjectMatcherTest extends TestCase
{
    private PropertyBagObjectMatcher $matcher;

    #[\Override]
    protected function setUp(): void
    {
        $this->matcher = new PropertyBagObjectMatcher();
    }

    public function testMatchesBagEntryHoldingObject(): void
    {
        $bag = new PropertyBag(Entity1::class, ['relation' => new \stdClass(), 'items' => new ArrayCollection()]);

        self::assertTrue($this->matcher->matches($bag, 'relation'));
        self::assertTrue($this->matcher->matches($bag, 'items'));
    }

    /**
     * @dataProvider nonObjectValueDataProvider
     */
    public function testDoesNotMatchBagEntryHoldingNonObject(mixed $value): void
    {
        self::assertFalse($this->matcher->matches(new PropertyBag(Entity1::class, ['field' => $value]), 'field'));
    }

    public function nonObjectValueDataProvider(): array
    {
        return [
            'string' => ['value'],
            'int' => [1],
            'empty array' => [[]],
            'array' => [['a']],
            'null' => [null],
        ];
    }

    public function testDoesNotMatchMissingEntry(): void
    {
        self::assertFalse($this->matcher->matches(new PropertyBag(Entity1::class, []), 'missing'));
    }

    public function testDoesNotMatchNonBagObject(): void
    {
        $object = new \stdClass();
        $object->relation = new \stdClass();

        self::assertFalse($this->matcher->matches($object, 'relation'));
    }

    public function testMatchesAnyPropertyBagImplementation(): void
    {
        $bag = new #[\AllowDynamicProperties] class () implements PropertyBagInterface {
            #[\Override]
            public function getOwnerClass(): string
            {
                return Entity1::class;
            }

            #[\Override]
            public function toArray(): array
            {
                return get_object_vars($this);
            }
        };
        $bag->relation = new \stdClass();

        self::assertTrue($this->matcher->matches($bag, 'relation'));
    }

    public function testMatchesPropertyBagImplementationWithNonPublicStorage(): void
    {
        $bag = new class () implements PropertyBagInterface {
            private \stdClass $relation;

            public function __construct()
            {
                $this->relation = new \stdClass();
            }

            #[\Override]
            public function getOwnerClass(): string
            {
                return Entity1::class;
            }

            #[\Override]
            public function toArray(): array
            {
                return ['relation' => $this->relation];
            }
        };

        self::assertTrue($this->matcher->matches($bag, 'relation'));
    }
}
