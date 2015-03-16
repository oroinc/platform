<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingEventDictionary;

class TrackingEventDictionaryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingEventDictionary
     */
    protected $trackingEvents;

    protected function setUp()
    {
        $this->trackingEvents = new TrackingEventDictionary();
    }

    public function testId()
    {
        $this->assertNull($this->trackingEvents->getId());
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
            $propertyAccessor->getValue($this->trackingEvents, $property)
        );

        $propertyAccessor->setValue($this->trackingEvents, $property, $value);

        $this->assertEquals(
            $expected,
            $propertyAccessor->getValue($this->trackingEvents, $property)
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        return [
            ['name', 'visit', 'visit'],
            ['visitEvents', [], []]
        ];
    }
}
