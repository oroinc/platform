<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarConnectionPropertyRepository")
 * @ORM\Table(name="oro_calendar_property",
 *      uniqueConstraints={@ORM\UniqueConstraint(name="oro_calendar_property_uq",
 *                              columns={"calendar_uid", "user_owner_id"})})
 */
class CalendarConnectionProperty
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * It is Unique ID of calendar
     *
     * @var string
     *
     * @ORM\Column(name="calendar_uid", type="string", length=32)
     */
    protected $calendar;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_owner_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $owner;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default"=1})
     */
    protected $visible = true;

    /**
     * Gets the connection id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets owning user for this calendar connection property
     *
     * @return User
     */
    public function getOwner()
    {
        return $this->owner;
    }

    /**
     * Sets owning user for this calendar
     *
     * @param User $owningUser
     * @return CalendarConnectionProperty
     */
    public function setOwner($owningUser)
    {
        $this->owner = $owningUser;

        return $this;
    }

    /**
     * Gets calendar uid.
     *
     * @return string
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets calendar uid
     *
     * @param string $calendar
     * @return CalendarConnectionProperty
     */
    public function setCalendar($calendar)
    {
        $this->calendar = $calendar;

        return $this;
    }

    /**
     * Gets visible property.
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sets visible property
     *
     * @param string $visible
     * @return CalendarConnectionProperty
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;

        return $this;
    }
}
