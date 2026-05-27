<?php

namespace Oro\Component\Duplicator\Tests\Unit\Filter;

use Oro\Component\Duplicator\Filter\ShallowCopyFilter;
use PHPUnit\Framework\TestCase;

class ShallowCopyFilterTest extends TestCase
{
    public function testApplyOnDirectProperty(): void
    {
        $related = new \stdClass();
        $related->name = 'original';

        $object = new class () {
            public ?\stdClass $related = null;
        };
        $object->related = $related;

        $filter = new ShallowCopyFilter();
        $filter->apply($object, 'related', fn () => null);

        self::assertNotSame($related, $object->related);
        self::assertSame('original', $object->related->name);
    }

    public function testApplyProducesClone(): void
    {
        $inner = new \stdClass();
        $inner->nested = new \stdClass();
        $inner->nested->value = 'deep';

        $object = new class () {
            public ?\stdClass $inner = null;
        };
        $object->inner = $inner;

        $filter = new ShallowCopyFilter();
        $filter->apply($object, 'inner', fn () => null);

        // Shallow copy: top-level object is different...
        self::assertNotSame($inner, $object->inner);
        // ...but nested reference is shared (shallow, not deep)
        self::assertSame($inner->nested, $object->inner->nested);
    }
}
