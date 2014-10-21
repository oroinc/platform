<?php

namespace Oro\Bundle\CalendarBundle\EventListener;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\UnitOfWork;

use Oro\Bundle\ActivityBundle\Manager\ActivityManager;
use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarConnection;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;

class EntityListener
{
    /** @var ActivityManager */
    protected $activityManager;

    /** @var ClassMetadata[] */
    protected $metadataLocalCache = [];

    /**
     * @param ActivityManager $activityManager
     */
    public function __construct(ActivityManager $activityManager)
    {
        $this->activityManager = $activityManager;
    }

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
            } elseif ($entity instanceof CalendarEvent) {
                $this->assignCalendarEventActivity($entity, $em, $uow);
            }

        }

        foreach ($uow->getScheduledEntityUpdates() as $entity) {
            if ($entity instanceof User) {
                $this->ensureDefaultUserCalendarExist($entity, $em, $uow);
            } elseif ($entity instanceof CalendarEvent) {
                $this->ensureCalendarEventActivityArranged($entity, $em, $uow);
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

        return (bool)$calendarRepository->findByUserAndOrganization($user->getId(), $organization->getId());
    }

    /**
     * @param CalendarEvent $entity
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     */
    protected function assignCalendarEventActivity(CalendarEvent $entity, EntityManager $em, UnitOfWork $uow)
    {
        $hasChanges = $this->activityManager->addActivityTarget($entity, $entity->getCalendar()->getOwner());
        // recompute change set if needed
        if ($hasChanges) {
            $uow->computeChangeSet($this->getClassMetadata($entity, $em), $entity);
        }
    }

    /**
     * @param CalendarEvent $entity
     * @param EntityManager $em
     * @param UnitOfWork    $uow
     */
    protected function ensureCalendarEventActivityArranged(CalendarEvent $entity, EntityManager $em, UnitOfWork $uow)
    {
        $hasChanges = false;
        $changeSet  = $uow->getEntityChangeSet($entity);
        foreach ($changeSet as $field => $values) {
            if ($field === 'calendar') {
                /** @var Calendar $oldValue */
                /** @var Calendar $newValue */
                list($oldValue, $newValue) = $values;
                if ($oldValue !== $newValue && $oldValue->getOwner() !== $newValue->getOwner()) {
                    $hasChanges |= $this->activityManager->replaceActivityTarget(
                        $entity,
                        $oldValue->getOwner(),
                        $newValue->getOwner()
                    );
                }
                break;
            }
        }
        // recompute change set if needed
        if ($hasChanges) {
            $uow->computeChangeSet($this->getClassMetadata($entity, $em), $entity);
        }
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
