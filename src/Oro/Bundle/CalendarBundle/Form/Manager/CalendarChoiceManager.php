<?php

namespace Oro\Bundle\CalendarBundle\Form\Manager;

use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;
use Oro\Bundle\CalendarBundle\Entity\Repository\SystemCalendarRepository;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\SystemCalendar;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Formatter\NameFormatter;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarChoiceManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var NameFormatter */
    protected $nameFormatter;

    /** @var TranslatorInterface */
    protected $translator;

    /**
     * @param DoctrineHelper      $doctrineHelper
     * @param SecurityFacade      $securityFacade
     * @param NameFormatter       $nameFormatter
     * @param TranslatorInterface $translator
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        NameFormatter $nameFormatter,
        TranslatorInterface $translator
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->nameFormatter  = $nameFormatter;
        $this->translator     = $translator;
    }

    /**
     * @param bool $isNew
     *
     * @return array key = calendarUid, value = calendar name
     */
    public function getChoices($isNew)
    {
        /** @var SystemCalendarRepository $systemCalendarRepo */
        $systemCalendarRepo = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:SystemCalendar');
        $calendars          = $systemCalendarRepo->getCalendarsQueryBuilder($this->securityFacade->getOrganizationId())
            ->select('sc.id, sc.name, sc.public')
            ->getQuery()
            ->getArrayResult();
        // @todo: check ACL here. will be done in BAP-6575

        if ($isNew && count($calendars) === 1) {
            $calendars[0]['name'] = $this->translator->trans(
                'oro.calendar.add_to_calendar',
                ['%name%' => $calendars[0]['name']]
            );
        } elseif (!$isNew || count($calendars) !== 0) {
            usort(
                $calendars,
                function ($a, $b) {
                    return strcasecmp($a['name'], $b['name']);
                }
            );
            /** @var CalendarRepository $calendarRepo */
            $calendarRepo  = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:Calendar');
            $userCalendars = $calendarRepo->getUserCalendarsQueryBuilder(
                $this->securityFacade->getOrganizationId(),
                $this->securityFacade->getLoggedUserId()
            )
                ->select('c.id, c.name')
                ->getQuery()
                ->getArrayResult();
            foreach ($userCalendars as $userCalendar) {
                if (empty($userCalendar['name'])) {
                    $userCalendar['name'] = $this->nameFormatter->format($this->securityFacade->getLoggedUser());
                }
                $userCalendar['alias'] = Calendar::CALENDAR_ALIAS;
                array_unshift($calendars, $userCalendar);
            }
        }

        $choices = [];
        foreach ($calendars as $calendar) {
            $alias = !empty($calendar['alias'])
                ? $calendar['alias']
                : ($calendar['public'] ? SystemCalendar::PUBLIC_CALENDAR_ALIAS : SystemCalendar::CALENDAR_ALIAS);

            $choices[$this->getCalendarUid($alias, $calendar['id'])] = $calendar['name'];
        }

        return $choices;
    }

    /**
     * @param CalendarEvent $event
     * @param string        $calendarAlias
     * @param int           $calendarId
     */
    public function setCalendar(CalendarEvent $event, $calendarAlias, $calendarId)
    {
        $calendar = $event->getCalendar();
        if ($calendarAlias === Calendar::CALENDAR_ALIAS) {
            if (!$calendar || $calendar->getId() !== $calendarId) {
                $event->setCalendar($this->findCalendar($calendarId));
            }
        } else {
            if ($calendar) {
                $event->setCalendar(null);
            }
            $event->setSystemCalendar($this->findSystemCalendar($calendarId));
        }
    }

    /**
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
}
