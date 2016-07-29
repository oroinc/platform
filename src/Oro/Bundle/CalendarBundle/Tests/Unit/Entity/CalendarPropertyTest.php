<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Entity;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarProperty;
use Oro\Bundle\CalendarBundle\Tests\Unit\ReflectionUtil;

class CalendarPropertyTest extends AbstractEntityTest
{
    /**
     * {@inheritDoc}
     */
    public function getEntityFQCN()
    {
        return 'Oro\Bundle\CalendarBundle\Entity\CalendarProperty';
    }

    public function testPositionDefault()
    {
        $obj = new CalendarProperty();
        $this->assertSame(0, $obj->getPosition());
    }

    public function testVisibleDefault()
    {
        $obj = new CalendarProperty();
        $this->assertTrue($obj->getVisible());
    }

    public function testToString()
    {
        $calendarProperty = new CalendarProperty();
        $this->assertEmpty((string) $calendarProperty);
        ReflectionUtil::setId($calendarProperty, 1);

        $this->assertEquals(1, (string) $calendarProperty);
    }

    /**
     * {@inheritDoc}
     */
    public function getSetDataProvider()
    {
        return [
            ['targetCalendar', new Calendar(), new Calendar()],
            ['calendarAlias', 'testAlias', 'testAlias'],
            ['calendar', 123, 123],
            ['position', 100, 100],
            ['visible', false, false],
            ['backgroundColor', '#FFFFFF', '#FFFFFF'],
        ];
    }
}
