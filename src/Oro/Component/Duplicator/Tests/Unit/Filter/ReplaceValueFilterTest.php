<?php

namespace Oro\Component\Duplicator\Tests\Unit\Filter;

use Oro\Component\Duplicator\Filter\ReplaceValueFilter;
use PHPUnit\Framework\TestCase;

class ReplaceValueFilterTest extends TestCase
{
    public function testApply(): void
    {
        $object = new class () {
            public string $value = 'old';
        };

        $filter = new ReplaceValueFilter('new');
        $filter->apply($object, 'value', fn () => null);

        self::assertSame('new', $object->value);
    }

    public function testApplyWithNullValue(): void
    {
        $object = new class () {
            public ?string $value = 'original';
        };

        $filter = new ReplaceValueFilter(null);
        $filter->apply($object, 'value', fn () => null);

        self::assertNull($object->value);
    }
}
