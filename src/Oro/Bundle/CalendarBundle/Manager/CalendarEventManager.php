<?php

namespace Oro\Bundle\CalendarBundle\Manager;

use Oro\Bundle\CalendarBundle\Entity\Attendee;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Oro\Bundle\CalendarBundle\Exception\CalendarEventRelatedAttendeeNotFoundException;
use Oro\Bundle\CalendarBundle\Exception\StatusNotFoundException;
use Oro\Bundle\CalendarBundle\Provider\SystemCalendarConfig;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\SecurityBundle\Exception\ForbiddenException;

class CalendarEventManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /** @var SystemCalendarConfig */
    protected $calendarConfig;

    /**
     * @param DoctrineHelper       $doctrineHelper
     * @param SecurityFacade       $securityFacade
     * @param EntityNameResolver   $entityNameResolver
     * @param SystemCalendarConfig $calendarConfig
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        EntityNameResolver $entityNameResolver,
        SystemCalendarConfig $calendarConfig
    ) {
        $this->doctrineHelper     = $doctrineHelper;
        $this->securityFacade     = $securityFacade;
        $this->entityNameResolver = $entityNameResolver;
        $this->calendarConfig     = $calendarConfig;
    }

    /**
     * Gets a list of system calendars for which it is granted to add events
     *
     * @return array of [id, name, public]
     */
    public function getSystemCalendars()
    {
        /** @var SystemCalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $calendars = $repo->getCalendarsQueryBuilder($this->securityFacade->getOrganizationId())
            ->select('sc.id, sc.name, sc.public')
            ->getQuery()
            ->getArrayResult();

        // @todo: check ACL here. will be done in BAP-6575

        return $calendars;
    }

    /**
     * @param CalendarEvent $event
     * @param string $newStatus
     *
     * @throws CalendarEventRelatedAttendeeNotFoundException
     * @throws StatusNotFoundException
     */
    public function changeStatus(CalendarEvent $event, $newStatus)
    {
        $relatedAttendee = $event->getRelatedAttendee();
        if (!$relatedAttendee) {
            throw new CalendarEventRelatedAttendeeNotFoundException();
        }

        $statusEnum = $this->doctrineHelper
            ->getEntityRepository(ExtendHelper::buildEnumValueClassName(Attendee::STATUS_ENUM_CODE))
            ->find($newStatus);

        if (!$statusEnum) {
            throw new StatusNotFoundException(sprintf('Status "%s" does not exists', $newStatus));
        }

        $relatedAttendee->setStatus($statusEnum);
    }

    /**
     * Gets a list of user's calendars for which it is granted to add events
     *
     * @return array of [id, name]
     */
    public function getUserCalendars()
    {
        /** @var CalendarRepository $repo */
        $repo      = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');
        $calendars = $repo->getUserCalendarsQueryBuilder(
            $this->securityFacade->getOrganizationId(),
            $this->securityFacade->getLoggedUserId()
        )
            ->select('c.id, c.name')
            ->getQuery()
            ->getArrayResult();
        foreach ($calendars as &$calendar) {
            if (empty($calendar['name'])) {
                $calendar['name'] = $this->entityNameResolver->getName($this->securityFacade->getLoggedUser());
            }
        }

        return $calendars;
    }

    /**
     * Links an event with a calendar by its alias and id
     *
     * @param CalendarEvent $event
     * @param string        $calendarAlias
     * @param int           $calendarId
     *
     * @throws \LogicException
     * @throws ForbiddenException
     */
    public function setCalendar(CalendarEvent $event, $calendarAlias, $calendarId)
    {
        if ($calendarAlias === Calendar::CALENDAR_ALIAS) {
            $calendar = $event->getCalendar();
            if (!$calendar || $calendar->getId() !== $calendarId) {
                $event->setCalendar($this->findCalendar($calendarId));
            }
        } elseif (in_array($calendarAlias, [SystemCalendar::CALENDAR_ALIAS, SystemCalendar::PUBLIC_CALENDAR_ALIAS])) {
            $systemCalendar = $this->findSystemCalendar($calendarId);
            //@TODO: Added permission verification
            if ($systemCalendar->isPublic() && !$this->calendarConfig->isPublicCalendarEnabled()) {
                throw new ForbiddenException('Public calendars are disabled.');
            }
            if (!$systemCalendar->isPublic() && !$this->calendarConfig->isSystemCalendarEnabled()) {
                throw new ForbiddenException('System calendars are disabled.');
            }
            $event->setSystemCalendar($systemCalendar);
        } else {
            throw new \LogicException(
                sprintf('Unexpected calendar alias: "%s". CalendarId: %d.', $calendarAlias, $calendarId)
            );
        }
    }

    /**
     * Gets UID of a calendar this event belongs to
     * The calendar UID is a string includes a calendar alias and id in the following format: {alias}_{id}
     *
     * @param string $calendarAlias
     * @param int    $calendarId
     *
     * @return string
     */
    public function getCalendarUid($calendarAlias, $calendarId)
    {
        return sprintf('%s_%d', $calendarAlias, $calendarId);
    }

    /**
     * Extracts calendar alias and id from a calendar UID
     *
     * @param string $calendarUid
     *
     * @return array [$calendarAlias, $calendarId]
     */
    public function parseCalendarUid($calendarUid)
    {
        $delim = strrpos($calendarUid, '_');

        return [
            substr($calendarUid, 0, $delim),
            (int)substr($calendarUid, $delim + 1)
        ];
    }

    /**
     * @param int $calendarId
     *
     * @return Calendar|null
     */
    protected function findCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar')
            ->find($calendarId);
    }

    /**
     * @param int $calendarId
     *
     * @return SystemCalendar|null
     */
    protected function findSystemCalendar($calendarId)
    {
        return $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar')
            ->find($calendarId);
    }

    /**
     * @param Recurrence $recurrence
     */
    public function removeRecurrence(Recurrence $recurrence)
    {
        $this->doctrineHelper->getEntityManager($recurrence)->remove($recurrence);
    }
}
