<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
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
    /** @var ClassMetadata[] */
    protected $metadataLocalCache = [];

    /**
     * @param OnFlushEventArgs $event
     */
    public function onFlush(OnFlushEventArgs $event)
    {
        $em  = $event->getEntityManager();
        $uow = $em->getUnitOfWork();

        foreach ($uow->getScheduledEntityInsertions() as $entity) {
            if ($entity instanceof User) {
                $this->ensureDefaultUserCalendarExist($entity, $em, $uow);
            }
        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $this->ensureDefaultUserCalendarExist($entity, $em, $uow);
            }
        }
    }

    /**
     * @param User          $entity
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     */
    protected function ensureDefaultUserCalendarExist(User $entity, EntityManager $em, UnitOfWork $uow)
    {
        $assignedOrganizations = $entity->getOrganizations();
        foreach ($assignedOrganizations as $organization) {
            if (!$this->isCalendarExists($em, $entity, $organization)) {
                $this->createCalendar($em, $uow, $entity, $organization);
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

        $uow->computeChangeSet($this->getClassMetadata($calendar, $em), $calendar);
        $uow->computeChangeSet($this->getClassMetadata($calendarConnection, $em), $calendarConnection);
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

        return (bool)$calendarRepository->findDefaultCalendar($user->getId(), $organization->getId());
    }

    /**
     * @param object        $entity
     * @param EntityManager $em
     *
     * @return ClassMetadata
     */
    protected function getClassMetadata($entity, EntityManager $em)
    {
        $className = ClassUtils::getClass($entity);
        if (!isset($this->metadataLocalCache[$className])) {
            $this->metadataLocalCache[$className] = $em->getClassMetadata($className);
        }

        return $this->metadataLocalCache[$className];
    }
}
