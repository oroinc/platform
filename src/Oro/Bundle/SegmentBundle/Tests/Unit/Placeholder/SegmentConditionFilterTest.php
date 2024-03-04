<?php

namespace Oro\Bundle\SegmentBundle\Tests\Unit\Placeholder;

use Oro\Bundle\SegmentBundle\Placeholder\SegmentConditionFilter;

class SegmentConditionFilterTest extends \PHPUnit\Framework\TestCase
{
    public function testIsSegmentFilterShouldBeAdded(): void
    {
        $filter = new SegmentConditionFilter();
        self::assertTrue($filter->isSegmentFilterShouldBeAdded());
    }
}
