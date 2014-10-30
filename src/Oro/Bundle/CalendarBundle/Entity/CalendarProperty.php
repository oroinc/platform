<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CalendarBundle\Model\ExtendCalendarProperty;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\UserBundle\Entity\User;

/**
 * This entity is used to store different kind of user's properties for a calendar.
 * The combination of calendarAlias and calendarId is unique identifier of a calendar.
 *
 * @ORM\Entity
 * @ORM\Table(
 *      name="oro_calendar_property",
 *      uniqueConstraints={
 *          @ORM\UniqueConstraint(name="oro_calendar_prop_uq", columns={"calendar_alias", "calendar_id", "user_id"})
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
     */
    protected $id;

    /**
     * @var User
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\UserBundle\Entity\User")
     * @ORM\JoinColumn(name="user_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     */
    protected $user;

    /**
     * @var string
     *
     * @ORM\Column(name="calendar_alias", type="string", length=32)
     */
    protected $calendarAlias;

    /**
     * @var int
     *
     * @ORM\Column(name="calendar_id", type="integer")
     */
    protected $calendarId;

    /**
     * @var boolean
     *
     * @ORM\Column(name="visible", type="boolean", options={"default"=true})
     */
    protected $visible = true;

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
     * Gets unique id of a calendar.
     *
     * @return string
     */
    public function getCalendarUid()
    {
        return sprintf('%s:%d', $this->calendarAlias, $this->calendarId);
    }

    /**
     * Sets unique id of a calendar.
     *
     * @param string $calendarAlias
     * @param int    $calendarId
     *
     * @return self
     */
    public function setCalendarUid($calendarAlias, $calendarId)
    {
        return $this->setCalendarAlias($calendarAlias)->setCalendarId($calendarId);
    }

    /**
     * Gets a calendar alias.
     *
     * @return string
     */
    public function getCalendarAlias()
    {
        return $this->calendarAlias;
    }

    /**
     * Sets a calendar alias
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
     * Gets a calendar id.
     *
     * @return int
     */
    public function getCalendarId()
    {
        return $this->calendarId;
    }

    /**
     * Sets a calendar id
     *
     * @param int $calendarId
     *
     * @return self
     */
    public function setCalendarId($calendarId)
    {
        $this->calendarId = $calendarId;

        return $this;
    }

    /**
     * Gets a user this set of calendar properties belong to
     *
     * @return User
     */
    public function getUser()
    {
        return $this->user;
    }

    /**
     * Sets a user this set of calendar properties belong to
     *
     * @param User $user
     *
     * @return self
     */
    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Gets a property indicates whether events of connected calendar should be displayed or not
     *
     * @return boolean
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Sets a property indicates whether events of connected calendar should be displayed or not
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
}
