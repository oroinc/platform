<?php

namespace Oro\Bundle\CalendarBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository;

class EventsToUsersTransformer implements DataTransformerInterface
{
    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param ManagerRegistry $registry
     * @param SecurityFacade  $securityFacade
     */
    public function __construct(ManagerRegistry $registry, SecurityFacade $securityFacade)
    {
        $this->registry = $registry;
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($value)
    {
        if (null === $value) {
            return null;
        }

        $users = new ArrayCollection();

        /** @var CalendarEvent $event */
        foreach ($value as $event) {
            $users->add($event->getCalendar()->getOwner());
        }

        return $users;
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($value)
    {
        if (!$value) {
            return [];
        }

        /** @var CalendarRepository $calendarRepository */
        $calendarRepository = $this->registry->getRepository('OroCalendarBundle:Calendar');
        $organizationId = $this->securityFacade->getOrganizationId();

        if (!$organizationId) {
            throw new TransformationFailedException('Can\'t get current organization');
        }

        $events = new ArrayCollection();

        /** @var User $user */
        $userIds = [];
        foreach ($value as $user) {
            $userIds[] = $user->getId();
        }

        $calendars = $calendarRepository->findDefaultCalendars($userIds, $organizationId);
        foreach ($calendars as $calendar) {
            $event = new CalendarEvent();
            $event->setCalendar($calendar);
            $events->add($event);
        }

        return $events;
    }
}
