<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;

class TrackingVisitEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingVisitEvent
     */
    protected $trackingVisitEvent;

    protected function setUp()
    {
        $this->trackingVisitEvent = new TrackingVisitEvent();
    }

    public function testId()
    {
        $this->assertNull($this->trackingVisitEvent->getId());
    }

    /**
     * @param string $property
     * @param mixed  $value
     * @param mixed  $expected
     *
     * @dataProvider propertyProvider
     */
    public function testProperties($property, $value, $expected)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->assertNull(
            $propertyAccessor->getValue($this->trackingVisitEvent, $property)
        );

        $propertyAccessor->setValue($this->trackingVisitEvent, $property, $value);

        $this->assertEquals(
            $expected,
            $propertyAccessor->getValue($this->trackingVisitEvent, $property)
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        $visit = new TrackingVisit();
        $event = new TrackingEventDictionary();
        $webEvent = new TrackingEvent();

        return [
            ['visit', $visit, $visit],
            ['event', $event, $event],
            ['webEvent', $webEvent, $webEvent],
        ];
    }
}
