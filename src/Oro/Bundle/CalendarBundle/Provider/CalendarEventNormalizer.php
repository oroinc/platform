<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ReminderBundle\Entity\Reminder;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventNormalizer
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /**
     * @param ManagerRegistry $doctrine
     * @param SecurityFacade  $securityFacade
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade
    ) {
        $this->doctrine       = $doctrine;
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param int          $calendarId
     * @param QueryBuilder $qb
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, QueryBuilder $qb)
    {
        $result = [];

        $items     = $qb->getQuery()->getArrayResult();
        $itemIds   = array_map(
            function ($item) {
                return $item['id'];
            },
            $items
        );
        $reminders = $this->doctrine->getRepository('OroReminderBundle:Reminder')
            ->findRemindersByEntities($itemIds, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

        foreach ($items as $item) {
            $resultItem = array();
            foreach ($item as $field => $value) {
                $this->transformEntityField($value);
                $resultItem[$field] = $value;
            }
            $resultItem['editable']  =
                ($resultItem['calendar'] === $calendarId)
                && $this->securityFacade->isGranted('oro_calendar_event_update');
            $resultItem['removable'] =
                ($resultItem['calendar'] === $calendarId)
                && $this->securityFacade->isGranted('oro_calendar_event_delete');
            $resultReminders         = array_filter(
                $reminders,
                function ($reminder) use ($resultItem) {
                    /* @var Reminder $reminder */
                    return $reminder->getRelatedEntityId() == $resultItem['id'];
                }
            );

            $resultItem['reminders'] = [];
            foreach ($resultReminders as $resultReminder) {
                /* @var Reminder $resultReminder */
                $resultItem['reminders'][] = [
                    'method'   => $resultReminder->getMethod(),
                    'interval' => [
                        'number' => $resultReminder->getInterval()->getNumber(),
                        'unit'   => $resultReminder->getInterval()->getUnit()
                    ]
                ];
            }

            $result[] = $resultItem;
        }

        return $result;
    }

    /**
     * Prepare entity field for serialization
     *
     * @param mixed $value
     */
    protected function transformEntityField(&$value)
    {
        if ($value instanceof Proxy && method_exists($value, '__toString')) {
            $value = (string)$value;
        } elseif ($value instanceof \DateTime) {
            $value = $value->format('c');
        }
    }
}
