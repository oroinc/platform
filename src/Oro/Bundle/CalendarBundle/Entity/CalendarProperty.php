<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use BeSimple\SoapBundle\ServiceDefinition\Annotation as Soap;

use Oro\Bundle\CalendarBundle\Model\ExtendCalendarProperty;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;

/**
 * This entity is used to store different kind of user's properties for a calendar.
 * The combination of calendarAlias and calendar is unique identifier of a calendar.
 *
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarPropertyRepository")
 * @ORM\Table(
 *      name="oro_calendar_property",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="oro_calendar_prop_uq",
 *              columns={"calendar_alias", "calendar_id", "target_calendar_id"}
 *          )
 *      }
 * )
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-cog"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "comment"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "immutable"=true
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          }
 *      }
 * )
 */
class CalendarProperty extends ExtendCalendarProperty
{
    /**
     * @var int
     *
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $id;

    /**
     * @var Calendar
     *
     * @ORM\ManyToOne(targetEntity="Calendar")
     * @ORM\JoinColumn(name="target_calendar_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * @Soap\ComplexType("Oro\Bundle\CalendarBundle\Entity\Calendar")
     */
    protected $targetCalendar;

    /**
     * @var string
     *
     * @ORM\Column(name="calendar_alias", type="string", length=32)
     * @Soap\ComplexType("string")
     */
    protected $calendarAlias;

    /**
     * @var int
     *
     * @ORM\Column(name="calendar_id", type="integer")
     * @Soap\ComplexType("int")
     */
    protected $calendar;

    /**
     * @var int
     *
     * @ORM\Column(name="position", type="integer", options={"default"=0})
     * @Soap\ComplexType("int", nillable=true)
     */
    protected $position = 0;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default"=true})
     * @Soap\ComplexType("boolean", nillable=true)
     */
    protected $visible = true;

    /**
     * @var string|null
     *
     * @ORM\Column(name="background_color", type="string", length=7, nullable=true)
     * @Soap\ComplexType("string", nillable=true)
     */
    protected $backgroundColor;

    /**
     * Gets id of this set of calendar properties.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets user's calendar this set of calendar properties belong to
     *
     * @return Calendar
     */
    public function getTargetCalendar()
    {
        return $this->targetCalendar;
    }

    /**
     * Sets user's calendar this set of calendar properties belong to
     *
     * @param Calendar $targetCalendar
     *
     * @return self
     */
    public function setTargetCalendar($targetCalendar)
    {
        $this->targetCalendar = $targetCalendar;

        return $this;
    }

    /**
     * Gets an alias of the connected calendar
     *
     * @return string
     */
    public function getCalendarAlias()
    {
        return $this->calendarAlias;
    }

    /**
     * Sets an alias of the connected calendar
     *
     * @param string $calendarAlias
     *
     * @return self
     */
    public function setCalendarAlias($calendarAlias)
    {
        $this->calendarAlias = $calendarAlias;

        return $this;
    }

    /**
     * Gets an id of the connected calendar
     *
     * @return int
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets an id of the connected calendar
     *
     * @param int $calendar
     *
     * @return self
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * Gets a number indicates where the connected calendar should be displayed
     *
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * Sets a number indicates where the connected calendar should be displayed
     *
     * @param int $position
     *
     * @return self
     */
    public function setPosition($position)
    {
        $this->position = $position;

        return $this;
    }

    /**
     * Gets a property indicates whether events of the connected calendar should be displayed or not
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sets a property indicates whether events of the connected calendar should be displayed or not
     *
     * @param bool $visible
     *
     * @return self
     */
    public function setVisible($visible)
    {
        $this->visible = (bool)$visible;

        return $this;
    }

    /**
     * Gets a background color of the connected calendar events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, for example F00 or FF0000 for a red color.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of the connected calendar events.
     *
     * @param string|null $backgroundColor The color in hex format, for example F00 or FF0000 for a red color.
     *                                     Set it to null to allow UI to calculate the background color automatically.
     *
     * @return self
     */
    public function setBackgroundColor($backgroundColor)
    {
        $this->backgroundColor = $backgroundColor;

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getId();
    }
}
