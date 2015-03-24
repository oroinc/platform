<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisit;
use Oro\Bundle\TrackingBundle\Entity\TrackingVisitEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;

class TrackingVisitEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var TrackingVisitEvent */
    protected $trackingVisitEvent;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->trackingVisitEvent = new TrackingVisitEvent();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->trackingVisitEvent);
    }

    public function testId()
    {
        $this->assertNull($this->trackingVisitEvent->getId());
    }

    public function testParsingCount()
    {
        $this->assertEquals(0, $this->trackingVisitEvent->getParsingCount());

        $this->trackingVisitEvent->setParsingCount(1);
        $this->assertEquals(1, $this->trackingVisitEvent->getParsingCount());
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
        $visit    = new TrackingVisit();
        $event    = new TrackingEventDictionary();
        $webEvent = new TrackingEvent();
        $website  = new TrackingWebsite();

        return [
            ['visit', $visit, $visit],
            ['event', $event, $event],
            ['webEvent', $webEvent, $webEvent],
            ['website', $website, $website]
        ];
    }
}
