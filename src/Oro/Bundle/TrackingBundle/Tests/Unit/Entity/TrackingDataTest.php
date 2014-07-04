<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\TrackingBundle\Entity\TrackingData;
use Oro\Bundle\TrackingBundle\Entity\TrackingEvent;

class TrackingDataTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TrackingData
     */
    protected $data;

    protected function setUp()
    {
        $this->data = new TrackingData();
    }

    public function testId()
    {
        $this->assertNull($this->data->getId());
    }

    public function testPrePersist()
    {
        $this->assertNull($this->data->getCreatedAt());
        $this->data->prePersist();
        $this->assertInstanceOf('\DateTime', $this->data->getCreatedAt());
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
            $propertyAccessor->getValue($this->data, $property)
        );

        $propertyAccessor->setValue($this->data, $property, $value);

        $this->assertEquals(
            $expected,
            $propertyAccessor->getValue($this->data, $property)
        );
    }

    /**
     * @return array
     */
    public function propertyProvider()
    {
        $date  = new \DateTime();
        $event = new TrackingEvent();

        return [
            ['data', '{"test": "test"}', '{"test": "test"}'],
            ['event', $event, $event],
            ['createdAt', $date, $date],
        ];
    }
}
