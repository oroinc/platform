<?php

namespace Oro\Bundle\CalendarBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\CalendarBundle\Model\ExtendCalendarEvent;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\ReminderBundle\Entity\RemindableInterface;
use Oro\Bundle\ReminderBundle\Model\ReminderData;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;

/**
 * @ORM\Entity(repositoryClass="Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository")
 * @ORM\Table(
 *      name="oro_calendar_event",
 *      indexes={
 *          @ORM\Index(name="oro_calendar_event_idx", columns={"calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_sys_calendar_event_idx", columns={"system_calendar_id", "start_at", "end_at"}),
 *          @ORM\Index(name="oro_calendar_event_up_idx", columns={"updated_at"})
 *      }
 * )
 * @ORM\HasLifecycleCallbacks()
 * @Config(
 *      routeName="oro_calendar_view_default",
 *      routeView="oro_calendar_event_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="icon-time"
 *          },
 *          "dataaudit"={
 *              "auditable"=true
 *          },
 *          "security"={
 *              "type"="ACL",
 *              "group_name"=""
 *              "permissions"="VIEW;CREATE;EDIT;DELETE",
 *          },
 *          "grouping"={
 *              "groups"={"activity"}
 *          },
 *          "reminder"={
 *              "reminder_template_name"="calendar_reminder",
 *              "reminder_flash_template_identifier"="calendar_event_template"
 *          },
 *          "note"={
 *              "immutable"=true
 *          },
 *          "activity"={
 *              "route"="oro_calendar_event_activity_view",
 *              "acl"="oro_calendar_view",
 *              "action_button_widget"="oro_add_calendar_event_button",
 *              "action_link_widget"="oro_add_calendar_event_link"
 *          },
 *          "attachment"={
 *              "immutable"=true
 *          },
 *          "grid"={
 *              "default"="calendar-event-grid",
 *              "context"="calendar-event-for-context-grid"
 *          }
 *      }
 * )
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CalendarEvent extends ExtendCalendarEvent implements RemindableInterface, DatesAwareInterface
{
    use DatesAwareTrait;

    /** @deprecated use constant with STATUS_ prefix */
    const NOT_RESPONDED        = self::STATUS_NOT_RESPONDED;
    /** @deprecated use constant with STATUS_ prefix */
    const TENTATIVELY_ACCEPTED = self::STATUS_TENTATIVELY_ACCEPTED;
    /** @deprecated use constant with STATUS_ prefix */
    const ACCEPTED             = self::STATUS_ACCEPTED;
    /** @deprecated use constant with STATUS_ prefix */
    const DECLINED             = self::STATUS_DECLINED;

    const STATUS_NOT_RESPONDED        = 'none';
    const STATUS_TENTATIVELY_ACCEPTED = 'tentative';
    const STATUS_ACCEPTED             = 'accepted';
    const STATUS_DECLINED             = 'declined';

    const WITHOUT_STATUS = null;
    
    const ORIGIN_ENUM_CODE     = 'oro_cal_event_origin';
    const ORIGIN_CLIENT        = 'client';
    const ORIGIN_SERVER        = 'server';
    const ORIGIN_EXTERNAL      = 'external';

    /**
     * @deprecated
     */
    protected $invitationStatuses = [
        CalendarEvent::STATUS_NOT_RESPONDED,
        CalendarEvent::STATUS_ACCEPTED,
        CalendarEvent::STATUS_TENTATIVELY_ACCEPTED,
        CalendarEvent::STATUS_DECLINED
    ];

    /**
     * @deprecated
     */
    protected $availableStatuses = [
        CalendarEvent::STATUS_ACCEPTED,
        CalendarEvent::STATUS_TENTATIVELY_ACCEPTED,
        CalendarEvent::STATUS_DECLINED
    ];

    /**
     * @deprecated
     *
     * @return array
     */
    public function getAvailableInvitationStatuses()
    {
        $currentStatus = $this->getInvitationStatus();
        $availableStatuses = [];
        if ($currentStatus) {
            $availableStatuses = $this->availableStatuses;
            if (($key = array_search($currentStatus, $availableStatuses)) !== false) {
                unset($availableStatuses[$key]);
            }
        }

        return $availableStatuses;
    }

    /**
     * @ORM\Id
     * @ORM\Column(name="id", type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var ArrayCollection
     *
     * @ORM\OneToMany(targetEntity="CalendarEvent", mappedBy="parent", orphanRemoval=true, cascade={"all"})
     **/
    protected $childEvents;

    /**
     * @var CalendarEvent
     *
     * @ORM\ManyToOne(targetEntity="CalendarEvent", inversedBy="childEvents")
     * @ORM\JoinColumn(name="parent_id", referencedColumnName="id", onDelete="CASCADE")
     **/
    protected $parent;

    /**
     * @var Calendar
     *
     * @ORM\ManyToOne(targetEntity="Calendar", inversedBy="events")
     * @ORM\JoinColumn(name="calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $calendar;

    /**
     * @var SystemCalendar
     *
     * @ORM\ManyToOne(targetEntity="SystemCalendar", inversedBy="events")
     * @ORM\JoinColumn(name="system_calendar_id", referencedColumnName="id", nullable=true, onDelete="CASCADE")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $systemCalendar;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $title;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $description;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="start_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $start;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="end_at", type="datetime")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $end;

    /**
     * @var bool
     *
     * @ORM\Column(name="all_day", type="boolean")
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $allDay;

    /**
     * @var string|null
     *
     * @ORM\Column(name="background_color", type="string", length=7, nullable=true)
     */
    protected $backgroundColor;

    /**
     * @var Collection
     */
    protected $reminders;

    /**
     * @var Collection|Attendee[]
     *
     * @ORM\OneToMany(
     *     targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee",
     *     mappedBy="calendarEvent",
     *     cascade={"all"},
     *     orphanRemoval=true
     * )
     * @ORM\OrderBy({"displayName"="ASC"})
     */
    protected $attendees;

    /**
     * @var Attendee
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CalendarBundle\Entity\Attendee", cascade={"persist", "remove"})
     * @ORM\JoinColumn(name="related_attendee", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $relatedAttendee;

    public function __construct()
    {
        parent::__construct();

        $this->reminders   = new ArrayCollection();
        $this->childEvents = new ArrayCollection();
        $this->attendees   = new ArrayCollection();
    }

    /**
     * Gets an calendar event id.
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @return string|null
     */
    public function getCalendarUid()
    {
        if ($this->calendar) {
            return sprintf('%s_%d', Calendar::CALENDAR_ALIAS, $this->calendar->getId());
        } elseif ($this->systemCalendar) {
            $alias = $this->systemCalendar->isPublic()
                ? SystemCalendar::PUBLIC_CALENDAR_ALIAS
                : SystemCalendar::CALENDAR_ALIAS;

            return sprintf('%s_%d', $alias, $this->systemCalendar->getId());
        }

        return null;
    }

    /**
     * Gets owning user's calendar
     *
     * @return Calendar|null
     */
    public function getCalendar()
    {
        return $this->calendar;
    }

    /**
     * Sets owning user's calendar
     *
     * @param Calendar $calendar
     *
     * @return self
     */
    public function setCalendar(Calendar $calendar = null)
    {
        $this->calendar = $calendar;
        // unlink an event from system calendar
        if ($calendar && $this->getSystemCalendar()) {
            $this->setSystemCalendar(null);
        }

        return $this;
    }

    /**
     * Gets owning system calendar
     *
     * @return SystemCalendar|null
     */
    public function getSystemCalendar()
    {
        return $this->systemCalendar;
    }

    /**
     * Sets owning system calendar
     *
     * @param SystemCalendar $systemCalendar
     *
     * @return self
     */
    public function setSystemCalendar(SystemCalendar $systemCalendar = null)
    {
        $this->systemCalendar = $systemCalendar;
        // unlink an event from user's calendar
        if ($systemCalendar && $this->getCalendar()) {
            $this->setCalendar(null);
        }

        return $this;
    }

    /**
     * Gets calendar event title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Sets calendar event title.
     *
     * @param string $title
     *
     * @return self
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Gets calendar event description.
     *
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Sets calendar event description.
     *
     * @param  string $description
     *
     * @return self
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Gets date/time an event begins.
     *
     * @return \DateTime
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * Sets date/time an event begins.
     *
     * @param \DateTime $start
     *
     * @return self
     */
    public function setStart($start)
    {
        $this->start = $start;

        return $this;
    }

    /**
     * Gets date/time an event ends.
     *
     * If an event is all-day the end date is inclusive.
     * This means an event with start Nov 10 and end Nov 12 will span 3 days on the calendar.
     *
     * If an event is NOT all-day the end date is exclusive.
     * This is only a gotcha when your end has time 00:00. It means your event ends on midnight,
     * and it will not span through the next day.
     *
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * Sets date/time an event ends.
     *
     * @param \DateTime $end
     *
     * @return self
     */
    public function setEnd($end)
    {
        $this->end = $end;

        return $this;
    }

    /**
     * Indicates whether an event occurs at a specific time-of-day.
     *
     * @return bool
     */
    public function getAllDay()
    {
        return $this->allDay;
    }

    /**
     * Sets a flag indicates whether an event occurs at a specific time-of-day.
     *
     * @param bool $allDay
     *
     * @return self
     */
    public function setAllDay($allDay)
    {
        $this->allDay = $allDay;

        return $this;
    }

    /**
     * Gets a background color of this events.
     * If this method returns null the background color should be calculated automatically on UI.
     *
     * @return string|null The color in hex format, e.g. #FF0000.
     */
    public function getBackgroundColor()
    {
        return $this->backgroundColor;
    }

    /**
     * Sets a background color of this events.
     *
     * @param string|null $backgroundColor The color in hex format, e.g. #FF0000.
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
     * {@inheritdoc}
     */
    public function getReminders()
    {
        return $this->reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function setReminders(Collection $reminders)
    {
        $this->reminders = $reminders;
    }

    /**
     * {@inheritdoc}
     */
    public function getReminderData()
    {
        if (!$this->getCalendar()) {
            throw new \LogicException(
                sprintf('Only user\'s calendar events can have reminders. Event Id: %d.', $this->id)
            );
        }

        $result = new ReminderData();
        $result->setSubject($this->getTitle());
        $result->setExpireAt($this->getStart());
        $result->setRecipient($this->getCalendar()->getOwner());

        return $result;
    }

    /**
     * Get child calendar events
     *
     * @return Collection|CalendarEvent[]
     */
    public function getChildEvents()
    {
        return $this->childEvents;
    }

    /**
     * Set children calendar events.
     *
     * @param Collection|CalendarEvent[] $calendarEvents
     *
     * @return CalendarEvent
     */
    public function resetChildEvents($calendarEvents)
    {
        $this->childEvents->clear();

        foreach ($calendarEvents as $calendarEvent) {
            $this->addChildEvent($calendarEvent);
        }

        return $this;
    }

    /**
     * Add child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function addChildEvent(CalendarEvent $calendarEvent)
    {
        if (!$this->childEvents->contains($calendarEvent)) {
            $this->childEvents->add($calendarEvent);
            $calendarEvent->setParent($this);
        }

        return $this;
    }

    /**
     * Remove child calendar event
     *
     * @param CalendarEvent $calendarEvent
     *
     * @return CalendarEvent
     */
    public function removeChildEvent(CalendarEvent $calendarEvent)
    {
        if ($this->childEvents->contains($calendarEvent)) {
            $this->childEvents->removeElement($calendarEvent);
            $calendarEvent->setParent(null);
        }

        return $this;
    }

    /**
     * @param Calendar $calendar
     *
     * @return CalendarEvent|null
     */
    public function getChildEventByCalendar(Calendar $calendar)
    {
        $result = $this->childEvents->filter(
            function (CalendarEvent $item) use ($calendar) {
                return $item->getCalendar() == $calendar;
            }
        );

        return $result->count() ? $result->first() : null;
    }

    /**
     * Set parent calendar event.
     *
     * @param CalendarEvent $parent
     */
    public function setParent(CalendarEvent $parent = null)
    {
        $this->parent = $parent;
    }

    /**
     * Get parent calendar event.
     *
     * @return CalendarEvent|null
     */
    public function getParent()
    {
        return $this->parent;
    }

    /**
     * @deprecated
     *
     * @return string|null
     */
    public function getInvitationStatus()
    {
        if (!$this->relatedAttendee) {
            return null;
        }

        $status = $this->relatedAttendee->getStatus();

        return $status
            ? $status->getId()
            : null;
    }

    /**
     * @return CalendarEvent
     */
    public function getRealCalendarEvent()
    {
        return $this->getParent() ?: $this;
    }


    /**
     * @return Collection|Attendee[]
     */
    public function getAttendees()
    {
        return $this->getRealCalendarEvent()->attendees;
    }

    /**
     * @return Collection|Attendee[]
     */
    public function getChildAttendees()
    {
        if ($this->parent) {
            return new ArrayCollection();
        }

        return $this->attendees->filter(function (Attendee $attendee) {
            return !$this->relatedAttendee || $attendee->getEmail() !== $this->relatedAttendee->getEmail();
        });
    }

    /**
     * @param Collection|Attendee[] $attendees
     *
     * @return CalendarEvent
     */
    public function setAttendees(Collection $attendees)
    {
        $this->getRealCalendarEvent()->attendees = $attendees;

        return $this;
    }

    /**
     * @param Attendee $attendee
     *
     * @return CalendarEvent
     */
    public function addAttendee(Attendee $attendee)
    {
        $attendees = $this->getRealCalendarEvent()->attendees;
        
        if (!$attendees->contains($attendee)) {
            $attendee->setCalendarEvent($this);
            $attendees->add($attendee);
        }

        return $this;
    }

    /**
     * Remove child calendar event
     *
     * @param Attendee $attendee
     *
     * @return CalendarEvent
     */
    public function removeAttendee(Attendee $attendee)
    {
        if ($this->getRealCalendarEvent()->attendees->contains($attendee)) {
            $this->getRealCalendarEvent()->attendees->removeElement($attendee);
        }

        return $this;
    }

    /**
     * @return Attendee
     */
    public function getRelatedAttendee()
    {
        return $this->relatedAttendee;
    }

    /**
     * @param Attendee $relatedAttendee
     *
     * @return CalendarEvent
     */
    public function setRelatedAttendee(Attendee $relatedAttendee)
    {
        $this->relatedAttendee = $relatedAttendee;
        $this->addAttendee($relatedAttendee);

        return $this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return (string)$this->getTitle();
    }
}
