<?php

namespace Oro\Bundle\CalendarBundle\Provider;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarEventRepository;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

class CalendarEventGuestsProvider
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var EntityNameResolver */
    protected $entityNameResolver;

    /**
     * @param DoctrineHelper     $helper
     * @param EntityNameResolver $resolver
     */
    public function __construct(DoctrineHelper $helper, EntityNameResolver $resolver)
    {
        $this->doctrineHelper     = $helper;
        $this->entityNameResolver = $resolver;
    }

    public function getGuestsInfo(CalendarEvent $event)
    {
        /** @var CalendarEventRepository $calendarEventRepository */
        $calendarEventRepository = $this->doctrineHelper->getEntityRepository('OroCalendarBundle:CalendarEvent');
        $userNameDQL             = $this->entityNameResolver->getNameDQL('Oro\Bundle\UserBundle\Entity\User', 'u');

        return $calendarEventRepository
            ->getInvitedUsersByParentsQueryBuilder([$event->getId()])
            ->select(
                'e.id, e.invitationStatus, u.email,' . sprintf('%s AS userFullName', $userNameDQL)
            )
            ->getQuery()->getArrayResult();
    }
}
