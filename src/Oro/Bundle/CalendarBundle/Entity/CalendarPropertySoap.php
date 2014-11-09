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
     * @var string
     *
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $calendarName;

    /**
     * @var boolean
     *
     * @Soap\ComplexType("boolean", nillable=true)
     */
    protected $removable;

    /**
     * @param array $calendarProperty
     */
    public function soapInit($calendarProperty)
    {
        $this->id = $calendarProperty['id'];
        $this->targetCalendar = $calendarProperty['targetCalendar'];
        $this->calendarAlias = $calendarProperty['calendarAlias'];
        $this->calendar = $calendarProperty['calendar'];
        $this->position = $calendarProperty['position'];
        $this->visible = $calendarProperty['visible'];
        $this->color = $calendarProperty['color'];
        $this->backgroundColor = $calendarProperty['backgroundColor'];
        $this->calendarName = $calendarProperty['calendarName'];
        $this->removable = $calendarProperty['removable'];
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

    /**
     * @param boolean $removable
     *
     * @return CalendarPropertySoap
     */
    public function setRemovable($removable)
    {
        $this->removable = $removable;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getRemovable()
    {
        return $this->removable;
    }
}
