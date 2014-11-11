<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Proxy\Proxy;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\ReminderBundle\Entity\Manager\ReminderManager;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class CalendarEventNormalizer
{
    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var SecurityFacade */
    protected $securityFacade;

    /** @var ReminderManager */
    protected $reminderManager;

    /**
     * @param ManagerRegistry $doctrine
     * @param SecurityFacade  $securityFacade
     * @param ReminderManager $reminderManager
     */
    public function __construct(
        ManagerRegistry $doctrine,
        SecurityFacade $securityFacade,
        ReminderManager $reminderManager
    ) {
        $this->doctrine        = $doctrine;
        $this->securityFacade  = $securityFacade;
        $this->reminderManager = $reminderManager;
    }

    /**
     * Converts calendar events returned by the given query to form that can be used in API
     *
     * @param int          $calendarId The target calendar id
     * @param QueryBuilder $qb         The query builder that should be used to get events
     *
     * @return array
     */
    public function getCalendarEvents($calendarId, QueryBuilder $qb)
    {
        $result = [];

        $items = $qb->getQuery()->getArrayResult();
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

            $result[] = $resultItem;
        }

        $this->reminderManager->applyReminders($result, 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent');

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
