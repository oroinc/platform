<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;
use Oro\Bundle\TrackingBundle\Entity\UniqueTrackingVisit;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class UniqueTrackingVisitTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testAccessors()
    {
        $this->assertPropertyAccessors(new UniqueTrackingVisit(), [
            ['id', 42],
            ['trackingWebsite', new TrackingWebsite()],
            ['visitCount', 1],
            ['userIdentifier', md5('abc')],
            ['firstActionTime', new \DateTime('2012-12-12')]
        ]);
    }
}
