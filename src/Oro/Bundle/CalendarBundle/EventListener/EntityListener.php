<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EntityListener
{
    /** @var ClassMetadata */
    protected $calendarMetadata;

    /** @var ClassMetadata */
    protected $calendarConnectionMetadata;

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $assignedOrganizations = $entity->getOrganizations();
                foreach ($assignedOrganizations as $organization) {
                    if (!$this->isCalendarExists($em, $entity, $organization)) {
                        $this->createCalendar($em, $uow, $entity, $organization);
                    }
                }
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $assignedOrganizations = $entity->getOrganizations();
                foreach ($assignedOrganizations as $organization) {
                    if (!$this->isCalendarExists($em, $entity, $organization)) {
                        $this->createCalendar($em, $uow, $entity, $organization);
                    }
                }
            }
        }
    }

    /**
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     * @param User          $entity
     * @param Organization  $organization
     */
    protected function createCalendar($em, $uow, $entity, $organization)
    {
        // create a default calendar for assigned organization
        $calendar = new Calendar();
        $calendar->setOwner($entity);
        $calendar->setOrganization($organization);
        // connect the calendar to itself
        $calendarConnection = new CalendarConnection($calendar);
        $calendar->addConnection($calendarConnection);

        $em->persist($calendar);
        $em->persist($calendarConnection);
        // can't inject entity manager through constructor because of circular dependency
        $uow->computeChangeSet($this->getCalendarMetadata($em), $calendar);
        $uow->computeChangeSet($this->getCalendarConnectionMetadata($em), $calendarConnection);
    }

    /**
     * @param EntityManager $em
     * @param User          $user
     * @param Organization  $organization
     *
     * @return bool
     */
    protected function isCalendarExists(EntityManager $em, User $user, Organization $organization)
    {
        $calendarRepository = $em->getRepository('OroCalendarBundle:Calendar');

        return (bool)$calendarRepository->findByUserAndOrganization($user->getId(), $organization->getId());
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return ClassMetadata
     */
    protected function getCalendarMetadata(EntityManager $entityManager)
    {
        if (!$this->calendarMetadata) {
            $this->calendarMetadata = $entityManager->getClassMetadata('OroCalendarBundle:Calendar');
        }

        return $this->calendarMetadata;
    }

    /**
     * @param EntityManager $entityManager
     *
     * @return ClassMetadata
     */
    protected function getCalendarConnectionMetadata(EntityManager $entityManager)
    {
        if (!$this->calendarConnectionMetadata) {
            $this->calendarConnectionMetadata
                = $entityManager->getClassMetadata('OroCalendarBundle:CalendarConnection');
        }

        return $this->calendarConnectionMetadata;
    }
}
