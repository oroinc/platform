<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SegmentBundle\Placeholder\SegmentConditionFilter;
use PHPUnit\Framework\TestCase;

class SegmentConditionFilterTest extends TestCase
{
    public function testIsSegmentFilterShouldBeAdded(): void
    {
        $filter = new SegmentConditionFilter();
        self::assertTrue($filter->isSegmentFilterShouldBeAdded());
    }
}
