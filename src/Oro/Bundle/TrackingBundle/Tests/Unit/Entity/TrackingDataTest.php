<?php

namespace Oro\Bundle\TrackingBundle\Tests\Unit\Entity;

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

    public function testPreUpdate()
    {
        $this->assertNull($this->data->getUpdatedAt());
        $this->data->preUpdate();
        $this->assertInstanceOf('\DateTime', $this->data->getUpdatedAt());
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
            $this->data->{'get' . $property}()
        );

        $this->data->{'set' . $property}($value);

        $this->assertEquals(
            $expected,
            $this->data->{'get' . $property}()
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
            ['updatedAt', $date, $date],
        ];
    }
}
