<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\SoapBundle\Entity\SoapEntityInterface;

/**
 * @Soap\Alias("Oro.Bundle.CalendarBundle.Entity.CalendarPropertySoap")
 */
class CalendarPropertySoap extends CalendarProperty implements SoapEntityInterface
{
    /**
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $calendarName;

    /**
     * @param CalendarProperty $calendarProperty
     */
    public function soapInit($calendarProperty)
    {
        $this->id = $calendarProperty->id;
        $this->targetCalendar = $calendarProperty->targetCalendar ? $calendarProperty->targetCalendar->getId() : null;
        $this->calendarAlias = $calendarProperty->calendarAlias;
        $this->calendar = $calendarProperty->calendar;
        $this->position = $calendarProperty->position;
        $this->visible = $this->visible;
        $this->color = $calendarProperty->color;
        $this->backgroundColor = $calendarProperty->backgroundColor;
        $this->calendarName = $calendarProperty->targetCalendar ? $this->targetCalendar->getName() : null;
    }

    /**
     * @param string|null $calendarName
     *
     * @return CalendarPropertySoap
     */
    public function setCalendarName($calendarName)
    {
        $this->calendarName = $calendarName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCalendarName()
    {
        return $this->calendarName;
    }
}
