<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

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

    public function testPreUpdate()
    {
        $this->assertNull($this->event->getUpdatedAt());
        $this->event->preUpdate();
        $this->assertInstanceOf('\DateTime', $this->event->getUpdatedAt());
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
        $this->assertNull(
            $this->event->{'get' . $property}()
        );

        $this->event->{'set' . $property}($value);

        $this->assertEquals(
            $expected,
            $this->event->{'get' . $property}()
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
            ['category', 'category', 'category'],
            ['action', 'action', 'action'],
            ['name', 'name', 'name'],
            ['value', 'value', 'value'],
            ['website', $website, $website],
            ['createdAt', $date, $date],
            ['updatedAt', $date, $date],
        ];
    }
}
