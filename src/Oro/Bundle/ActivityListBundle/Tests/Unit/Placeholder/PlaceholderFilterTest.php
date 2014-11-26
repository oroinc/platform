<?php

namespace Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder;

use Oro\Bundle\ActivityListBundle\Placeholder\PlaceholderFilter;
use Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget;

class PlaceholderFilterTest extends \PHPUnit_Framework_TestCase
{
    public function testSsApplicable()
    {
        $activityListProvider = $this
            ->getMockBuilder('Oro\Bundle\ActivityListBundle\Provider\ActivityListChainProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $filter = new PlaceholderFilter($activityListProvider);

        $activityListProvider->expects($this->any())
            ->method('getTargetEntityClasses')
            ->will($this->returnValue(['Oro\Bundle\ActivityListBundle\Tests\Unit\Placeholder\Fixture\TestTarget']));

        $this->assertTrue($filter->isApplicable(new TestTarget()));
        $this->assertFalse($filter->isApplicable(new \stdClass()));
        $this->assertFalse($filter->isApplicable(null));
    }
}
