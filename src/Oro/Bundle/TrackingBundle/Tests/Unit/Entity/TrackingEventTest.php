<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;

class TrackingEventTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingEvent
     */
    protected $event;

    protected function setUp()
    {
        $this->event = new TrackingEvent();
    }

    public function testId()
    {
        $this->assertNull($this->event->getId());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->event->getCreatedAt());
        $this->event->prePersist();
        $this->assertInstanceOf('\DateTime', $this->event->getCreatedAt());
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
            $propertyAccessor->getValue($this->event, $property)
        );

        $propertyAccessor->setValue($this->event, $property, $value);

        $this->assertEquals(
            $expected,
            $propertyAccessor->getValue($this->event, $property)
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        $website = new TrackingWebsite();
        $date    = new \DateTime();

        return [
            ['name', 'name', 'name'],
            ['value', 'value', 'value'],
            ['user', 'user', 'user'],
            ['website', $website, $website],
            ['createdAt', $date, $date],
            ['loggedAt', $date, $date],
        ];
    }
}
