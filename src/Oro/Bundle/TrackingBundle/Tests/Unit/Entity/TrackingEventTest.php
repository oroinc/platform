<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Oro\Bundle\TrackingBundle\Entity\TrackingData;
use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;
use Oro\Bundle\TrackingBundle\Entity\TrackingWebsite;

class TrackingEventTest extends \PHPUnit_Framework_TestCase
{
    /** @var TrackingEvent */
    protected $event;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->event = new TrackingEvent();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->event);
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
     * @param bool   $isBool
     *
     * @dataProvider propertyProvider
     */
    public function testProperties($property, $value, $expected, $isBool = false)
    {
        $propertyAccessor = PropertyAccess::createPropertyAccessor();

        if ($isBool) {
            $this->assertFalse(
                $propertyAccessor->getValue($this->event, $property)
            );
        } else {
            $this->assertNull(
                $propertyAccessor->getValue($this->event, $property)
            );
        }

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
        $website   = new TrackingWebsite();
        $eventData = new TrackingData();
        $date      = new \DateTime();

        return [
            ['name', 'name', 'name'],
            ['value', 1, 1],
            ['userIdentifier', 'userIdentifier', 'userIdentifier'],
            ['url', 'url', 'url'],
            ['title', 'title', 'title'],
            ['code', 'code', 'code'],
            ['website', $website, $website],
            ['createdAt', $date, $date],
            ['loggedAt', $date, $date],
            ['eventData', $eventData, $eventData],
            ['parsed', 1, 1, true]
        ];
    }
}
