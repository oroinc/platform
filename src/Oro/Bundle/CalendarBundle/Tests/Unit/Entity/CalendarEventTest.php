<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Symfony\Component\PropertyAccess\PropertyAccess;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class CalendarEventTest extends \PHPUnit_Framework_TestCase
{
    public function testIdGetter()
    {
        $obj = new CalendarEvent();
        ReflectionUtil::setId($obj, 1);
        $this->assertEquals(1, $obj->getId());
    }

    /**
     * @dataProvider propertiesDataProvider
     *
     * @param string $property
     * @param mixed  $value
     */
    public function testSettersAndGetters($property, $value)
    {
        $obj = new CalendarEvent();

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($obj, $property, $value);
        $this->assertSame($value, $accessor->getValue($obj, $property));
    }

    public function propertiesDataProvider()
    {
        return array(
            array('calendar', new Calendar()),
            array('title', 'testTitle'),
            array('description', 'testdDescription'),
            array('start', new \DateTime()),
            array('end', new \DateTime()),
            array('allDay', true),
            array('backgroundColor', '#FF0000'),
            array('createdAt', new \DateTime()),
            array('updatedAt', new \DateTime()),
        );
    }

    public function testPrePersist()
    {
        $obj = new CalendarEvent();

        $this->assertNull($obj->getCreatedAt());
        $this->assertNull($obj->getUpdatedAt());

        $obj->prePersist();
        $this->assertInstanceOf('\DateTime', $obj->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $obj = new CalendarEvent();

        $this->assertNull($obj->getUpdatedAt());

        $obj->preUpdate();
        $this->assertInstanceOf('\DateTime', $obj->getUpdatedAt());
    }
}
